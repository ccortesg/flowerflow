<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Enums\JudgeProfileStatus;
use App\Enums\RubricVersionStatus;
use App\Exceptions\AssignmentOperationRejected;
use App\Models\Competition;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\User;
use App\Services\AssignmentEligibility;
use App\Services\AuditLogger;
use App\Services\EvaluationRubricContract;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class AssignJudgesToSubmission
{
    public function __construct(
        private EnsureAssignmentAdministrator $ensureActor,
        private AssignmentEligibility $eligibility,
        private EvaluationRubricContract $rubricContract,
        private SendJudgeAssignmentNotification $sendNotification,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $judgeProfilePublicIds
     * @return array{created:Collection<int,JudgeAssignment>,omitted:int,notification_requested:bool}
     */
    public function execute(
        Submission $submission,
        User $actor,
        array $judgeProfilePublicIds,
        string $reason,
        bool $notify,
    ): array {
        $this->ensureActor->execute($actor, 'manage evaluation assignments');
        if ($notify && ! config('flowerflow.judge_notifications.assignment_enabled')) {
            throw ValidationException::withMessages(['notify_judges' => 'La notificación de nuevas asignaciones está deshabilitada globalmente.']);
        }

        $normalizedIds = array_values(array_map('strval', $judgeProfilePublicIds));
        if ($normalizedIds === [] || count($normalizedIds) !== count(array_unique($normalizedIds))) {
            throw ValidationException::withMessages(['judge_profiles' => 'Selecciona uno o más jueces sin repetir identificadores.']);
        }

        try {
            $result = DB::transaction(function () use ($submission, $actor, $normalizedIds, $reason, $notify): array {
                $version = $this->eligibility->requireCurrentVersion($submission, true);
                Competition::query()->whereKey($submission->competition_id)->lockForUpdate()->firstOrFail();
                $rubrics = RubricVersion::query()
                    ->where('competition_id', $submission->competition_id)
                    ->where('status', RubricVersionStatus::Active)
                    ->with('criteria')
                    ->lockForUpdate()
                    ->get();
                if ($rubrics->count() !== 1) {
                    throw new AssignmentOperationRejected('active_rubric_not_deterministic', 'Debe existir exactamente una rúbrica activa para asignar jueces.');
                }
                $rubric = $rubrics->sole();
                try {
                    $this->rubricContract->assertPersisted($rubric);
                } catch (LogicException) {
                    throw new AssignmentOperationRejected('active_rubric_invalid', 'La rúbrica activa no coincide con el catálogo aprobado.');
                }

                $profiles = JudgeProfile::query()
                    ->whereIn('public_id', $normalizedIds)
                    ->with('user.roles')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if ($profiles->count() !== count($normalizedIds)) {
                    throw new AssignmentOperationRejected('selected_judge_unknown', 'Uno o más jueces seleccionados no existen.');
                }
                foreach ($profiles as $profile) {
                    if ($profile->status !== JudgeProfileStatus::Active
                        || ! $profile->user
                        || ! $profile->user->hasExactRoles(['judge'])
                        || ! $profile->user->hasVerifiedEmail()
                        || $profile->password_initialized_at === null
                        || $profile->max_active_assignments !== null) {
                        throw new AssignmentOperationRejected('selected_judge_ineligible', 'Todos los jueces seleccionados deben estar activos, verificados y con configuración completa.');
                    }
                }

                $existingJudgeIds = JudgeAssignment::query()
                    ->where('submission_version_id', $version->id)
                    ->where('current_slot', 1)
                    ->lockForUpdate()
                    ->pluck('judge_profile_id');
                $created = collect();
                $dueAt = $this->dueAt();
                $now = now('UTC');
                foreach ($profiles as $profile) {
                    if ($existingJudgeIds->contains($profile->id)) {
                        continue;
                    }

                    $assignment = new JudgeAssignment;
                    $assignment->forceFill([
                        'competition_id' => $submission->competition_id,
                        'submission_version_id' => $version->id,
                        'judge_profile_id' => $profile->id,
                        'rubric_version_id' => $rubric->id,
                        'type' => JudgeAssignmentType::Initial->value,
                        'status' => JudgeAssignmentStatus::Active->value,
                        'current_slot' => 1,
                        'due_at' => $dueAt,
                        'replaces_assignment_id' => null,
                        'assigned_by_user_id' => $actor->id,
                        'assignment_reason' => trim($reason),
                        'assigned_at' => $now,
                    ])->save();
                    $created->push($assignment);
                    $existingJudgeIds->push($profile->id);
                    $this->audit->record('assignment.created', $assignment, $actor, [
                        'assignment_id' => $assignment->id,
                        'submission_version_id' => $version->id,
                        'judge_profile_id' => $profile->id,
                        'rubric_version_id' => $rubric->id,
                        'rubric_version' => $rubric->version,
                        'notification_requested' => $notify,
                    ]);
                }

                return [
                    'created' => $created,
                    'omitted' => count($normalizedIds) - $created->count(),
                    'notification_requested' => $notify,
                ];
            }, 5);
        } catch (AssignmentOperationRejected $exception) {
            $this->audit->record('assignment.creation_rejected', $submission, $actor, [
                'selected_count' => count($normalizedIds),
                'reason_code' => $exception->reasonCode,
            ]);

            throw ValidationException::withMessages(['judge_profiles' => $exception->getMessage()]);
        }

        if ($notify) {
            foreach ($result['created'] as $assignment) {
                $this->sendNotification->execute($assignment, $actor);
            }
        } else {
            foreach ($result['created'] as $assignment) {
                $this->audit->record('assignment.notification_skipped', $assignment, $actor, [
                    'assignment_id' => $assignment->id,
                    'notification_requested' => false,
                    'reason_code' => 'not_requested',
                ]);
            }
        }

        return $result;
    }

    private function dueAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            (string) config('flowerflow.evaluation_close_at'),
            (string) config('flowerflow.timezone'),
        )->utc();
    }
}
