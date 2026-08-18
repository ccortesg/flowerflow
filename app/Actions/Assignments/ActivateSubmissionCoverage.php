<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeAssignmentRole;
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

final class ActivateSubmissionCoverage
{
    public function __construct(
        private EnsureAssignmentAdministrator $ensureActor,
        private AssignmentEligibility $eligibility,
        private EvaluationRubricContract $rubricContract,
        private AuditLogger $audit,
    ) {}

    /** @return Collection<int, JudgeAssignment> */
    public function execute(Submission $submission, User $actor, string $reason): Collection
    {
        $this->ensureActor->execute($actor, 'manage evaluation assignments');

        try {
            return DB::transaction(function () use ($submission, $actor, $reason): Collection {
                $version = $this->eligibility->requireCurrentVersion($submission, true);
                Competition::query()->whereKey($submission->competition_id)->lockForUpdate()->firstOrFail();

                $rubrics = RubricVersion::query()
                    ->where('competition_id', $submission->competition_id)
                    ->where('status', RubricVersionStatus::Active)
                    ->lockForUpdate()
                    ->with('criteria')
                    ->get();

                if ($rubrics->count() !== 1) {
                    throw new AssignmentOperationRejected('active_rubric_not_deterministic', 'Debe existir exactamente una rúbrica activa para crear la cobertura.');
                }

                $rubric = $rubrics->sole();
                try {
                    $this->rubricContract->assertPersisted($rubric);
                } catch (LogicException) {
                    throw new AssignmentOperationRejected('active_rubric_invalid', 'La rúbrica activa no coincide con el contrato aprobado.');
                }

                $profiles = JudgeProfile::query()
                    ->where('status', JudgeProfileStatus::Active)
                    ->with('user.roles')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $primaries = $profiles
                    ->filter(fn (JudgeProfile $profile): bool => $profile->assignment_role === JudgeAssignmentRole::Primary)
                    ->values();
                $substitutes = $profiles
                    ->filter(fn (JudgeProfile $profile): bool => $profile->assignment_role === JudgeAssignmentRole::Substitute)
                    ->values();

                if ($primaries->count() !== 4 || $substitutes->count() !== 2) {
                    throw new AssignmentOperationRejected('invalid_active_judge_composition', 'La cobertura exige exactamente cuatro jueces principales activos y dos sustitutos activos.');
                }

                foreach ($profiles as $profile) {
                    if (! $profile->user
                        || ! $profile->user->hasExactRoles(['judge'])
                        || ! $profile->user->hasVerifiedEmail()
                        || ! $profile->password_initialized_at
                        || $profile->max_active_assignments !== null) {
                        throw new AssignmentOperationRejected('invalid_active_judge_prerequisites', 'Todos los jueces activos deben conservar rol exclusivo y prerrequisitos de acceso completos.');
                    }
                }

                $existing = JudgeAssignment::query()
                    ->where('submission_version_id', $version->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($existing->isNotEmpty()) {
                    if ($this->isExactExistingCoverage($existing, $primaries, $rubric, $this->dueAt())) {
                        return $existing;
                    }

                    throw new AssignmentOperationRejected('divergent_existing_coverage', 'La propuesta ya tiene cobertura divergente y no se modificó.');
                }

                $dueAt = $this->dueAt();
                $now = now('UTC');
                $created = $primaries->map(function (JudgeProfile $profile) use ($submission, $version, $rubric, $actor, $reason, $dueAt, $now): JudgeAssignment {
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

                    return $assignment;
                });

                $this->audit->record('assignment.coverage_created', $version, $actor, [
                    'competition_id' => $submission->competition_id,
                    'submission_version_id' => $version->id,
                    'rubric_version_id' => $rubric->id,
                    'assignment_ids' => $created->pluck('id')->all(),
                    'required' => 4,
                ]);

                return $created;
            }, 5);
        } catch (AssignmentOperationRejected $exception) {
            $this->audit->record('assignment.coverage_rejected', $submission, $actor, [
                'reason_code' => $exception->reasonCode,
            ]);

            throw ValidationException::withMessages(['coverage' => $exception->getMessage()]);
        }
    }

    private function dueAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            (string) config('flowerflow.evaluation_close_at'),
            (string) config('flowerflow.timezone'),
        )->utc();
    }

    /**
     * @param  Collection<int, JudgeAssignment>  $existing
     * @param  Collection<int, JudgeProfile>  $primaries
     */
    private function isExactExistingCoverage(
        Collection $existing,
        Collection $primaries,
        RubricVersion $rubric,
        CarbonImmutable $dueAt,
    ): bool {
        return $existing->count() === 4
            && $existing->every(fn (JudgeAssignment $assignment): bool => $assignment->type === JudgeAssignmentType::Initial
                && $assignment->status === JudgeAssignmentStatus::Active
                && $assignment->current_slot === 1
                && $assignment->rubric_version_id === $rubric->id
                && $assignment->replaces_assignment_id === null
                && $assignment->due_at->equalTo($dueAt))
            && $existing->pluck('judge_profile_id')->sort()->values()->all() === $primaries->pluck('id')->sort()->values()->all();
    }
}
