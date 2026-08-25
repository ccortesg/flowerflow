<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Enums\JudgeConflictStatus;
use App\Enums\JudgeProfileStatus;
use App\Exceptions\AssignmentOperationRejected;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Services\AssignmentEligibility;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ResolveJudgeConflict
{
    public function __construct(
        private EnsureAssignmentAdministrator $ensureActor,
        private AssignmentEligibility $eligibility,
        private SendJudgeAssignmentNotification $sendNotification,
        private AuditLogger $audit,
    ) {}

    public function execute(
        JudgeConflict $conflict,
        User $actor,
        string $judgeProfilePublicId,
        string $reason,
        bool $notify = false,
    ): JudgeAssignment {
        $this->ensureActor->execute($actor, 'resolve evaluation conflicts');
        if ($notify && ! config('flowerflow.judge_notifications.assignment_enabled')) {
            throw ValidationException::withMessages(['notify_judge' => 'La notificación de nuevas asignaciones está deshabilitada globalmente.']);
        }

        try {
            $replacement = DB::transaction(function () use ($conflict, $actor, $judgeProfilePublicId, $reason, $notify): JudgeAssignment {
                $lockedConflict = JudgeConflict::query()->whereKey($conflict->id)->lockForUpdate()->firstOrFail();
                if ($lockedConflict->status === JudgeConflictStatus::ResolvedReassigned
                    && $lockedConflict->replacement_assignment_id) {
                    return JudgeAssignment::query()->findOrFail($lockedConflict->replacement_assignment_id);
                }

                $original = JudgeAssignment::query()->whereKey($lockedConflict->judge_assignment_id)->lockForUpdate()->firstOrFail();
                if ($lockedConflict->status !== JudgeConflictStatus::Declared
                    || $original->status !== JudgeAssignmentStatus::ConflictDeclared) {
                    throw new AssignmentOperationRejected('conflict_not_resolvable', 'El conflicto ya no está disponible para resolución.');
                }

                $pinnedVersion = SubmissionVersion::query()->whereKey($original->submission_version_id)->lockForUpdate()->firstOrFail();
                $submission = Submission::query()->whereKey($pinnedVersion->submission_id)->firstOrFail();
                $currentVersion = $this->eligibility->requireCurrentVersion($submission, true);
                if ($currentVersion->id !== $original->submission_version_id) {
                    throw new AssignmentOperationRejected('submission_version_changed', 'La versión vigente de la propuesta cambió; no se realizó la reasignación.');
                }
                RubricVersion::query()->whereKey($original->rubric_version_id)->lockForUpdate()->firstOrFail();

                $selected = JudgeProfile::query()
                    ->where('public_id', $judgeProfilePublicId)
                    ->where('status', JudgeProfileStatus::Active)
                    ->with('user.roles')
                    ->lockForUpdate()
                    ->first();
                if (! $selected
                    || ! $selected->user
                    || ! $selected->user->hasExactRoles(['judge'])
                    || ! $selected->user->hasVerifiedEmail()
                    || $selected->password_initialized_at === null
                    || $selected->max_active_assignments !== null) {
                    throw new AssignmentOperationRejected('selected_judge_invalid', 'Selecciona un juez activo, verificado y con configuración completa.');
                }

                $hasCurrentAssignment = JudgeAssignment::query()
                    ->where('submission_version_id', $original->submission_version_id)
                    ->where('judge_profile_id', $selected->id)
                    ->where('current_slot', 1)
                    ->lockForUpdate()
                    ->exists();
                $declaredConflictBefore = JudgeConflict::query()
                    ->whereHas('assignment', fn ($query) => $query
                        ->where('submission_version_id', $original->submission_version_id)
                        ->where('judge_profile_id', $selected->id))
                    ->lockForUpdate()
                    ->exists();
                if ($selected->id === $original->judge_profile_id || $hasCurrentAssignment || $declaredConflictBefore) {
                    throw new AssignmentOperationRejected('selected_judge_ineligible_for_version', 'El juez seleccionado ya está asignado o declaró conflicto con esta propuesta.');
                }

                $now = now('UTC');
                DB::table('judge_assignments')->where('id', $original->id)->update([
                    'status' => JudgeAssignmentStatus::Voided->value,
                    'current_slot' => null,
                    'voided_by_user_id' => $actor->id,
                    'void_reason' => trim($reason),
                    'voided_at' => $now,
                    'updated_at' => $now,
                ]);

                $replacement = new JudgeAssignment;
                $replacement->forceFill([
                    'competition_id' => $original->competition_id,
                    'submission_version_id' => $original->submission_version_id,
                    'judge_profile_id' => $selected->id,
                    'rubric_version_id' => $original->rubric_version_id,
                    'type' => JudgeAssignmentType::Replacement->value,
                    'status' => JudgeAssignmentStatus::Active->value,
                    'current_slot' => 1,
                    'due_at' => $original->due_at,
                    'replaces_assignment_id' => $original->id,
                    'assigned_by_user_id' => $actor->id,
                    'assignment_reason' => trim($reason),
                    'assigned_at' => $now,
                ])->save();

                DB::table('judge_conflicts')->where('id', $lockedConflict->id)->update([
                    'status' => JudgeConflictStatus::ResolvedReassigned->value,
                    'resolved_by_user_id' => $actor->id,
                    'resolution_reason' => trim($reason),
                    'resolved_at' => $now,
                    'replacement_assignment_id' => $replacement->id,
                    'updated_at' => $now,
                ]);

                $this->audit->record('assignment.replacement_created', $replacement, $actor, [
                    'assignment_id' => $replacement->id,
                    'replaces_assignment_id' => $original->id,
                    'submission_version_id' => $original->submission_version_id,
                    'rubric_version_id' => $original->rubric_version_id,
                    'judge_profile_id' => $selected->id,
                    'notification_requested' => $notify,
                ]);
                $this->audit->record('assignment.conflict_resolved_reassigned', $lockedConflict, $actor, [
                    'assignment_id' => $original->id,
                    'replacement_assignment_id' => $replacement->id,
                    'status' => JudgeConflictStatus::ResolvedReassigned->value,
                ]);

                return $replacement;
            }, 5);
        } catch (AssignmentOperationRejected $exception) {
            $this->audit->record('assignment.replacement_rejected', $conflict, $actor, [
                'reason_code' => $exception->reasonCode,
            ]);
            throw ValidationException::withMessages(['judge_profile' => $exception->getMessage()]);
        }

        if ($notify) {
            $this->sendNotification->execute($replacement, $actor);
        } else {
            $this->audit->record('assignment.notification_skipped', $replacement, $actor, [
                'assignment_id' => $replacement->id,
                'notification_requested' => false,
                'reason_code' => 'not_requested',
            ]);
        }

        return $replacement;
    }
}
