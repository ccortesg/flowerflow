<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeAssignmentRole;
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
        private AuditLogger $audit,
    ) {}

    public function execute(JudgeConflict $conflict, User $actor, string $substitutePublicId, string $reason): JudgeAssignment
    {
        $this->ensureActor->execute($actor, 'resolve evaluation conflicts');

        try {
            return DB::transaction(function () use ($conflict, $actor, $substitutePublicId, $reason): JudgeAssignment {
                $lockedConflict = JudgeConflict::query()->whereKey($conflict->id)->lockForUpdate()->firstOrFail();

                if ($lockedConflict->status === JudgeConflictStatus::ResolvedReassigned
                    && $lockedConflict->replacement_assignment_id) {
                    return JudgeAssignment::query()->findOrFail($lockedConflict->replacement_assignment_id);
                }

                $original = JudgeAssignment::query()
                    ->whereKey($lockedConflict->judge_assignment_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedConflict->status !== JudgeConflictStatus::Declared
                    || $original->status !== JudgeAssignmentStatus::ConflictDeclared) {
                    throw new AssignmentOperationRejected('conflict_not_resolvable', 'El conflicto ya no está disponible para resolución.');
                }

                if ($original->type !== JudgeAssignmentType::Initial) {
                    throw new AssignmentOperationRejected('substitute_conflict_without_replacement', 'El sustituto declaró conflicto y no existe otro reemplazo aprobado.');
                }

                $originalProfile = JudgeProfile::query()->whereKey($original->judge_profile_id)->lockForUpdate()->firstOrFail();
                if ($originalProfile->assignment_role !== JudgeAssignmentRole::Primary) {
                    throw new AssignmentOperationRejected('original_assignment_not_primary', 'La asignación original no pertenece a un juez principal.');
                }

                $pinnedVersion = SubmissionVersion::query()
                    ->whereKey($original->submission_version_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $submission = Submission::query()->whereKey($pinnedVersion->submission_id)->firstOrFail();
                $currentVersion = $this->eligibility->requireCurrentVersion($submission, true);
                if ($currentVersion->id !== $original->submission_version_id) {
                    throw new AssignmentOperationRejected('submission_version_changed', 'La versión vigente de la propuesta cambió; no se realizó la reasignación.');
                }

                RubricVersion::query()->whereKey($original->rubric_version_id)->lockForUpdate()->firstOrFail();
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
                    throw new AssignmentOperationRejected('invalid_active_judge_composition', 'Debe existir exactamente una composición activa de cuatro jueces principales y dos sustitutos.');
                }

                foreach ($profiles as $profile) {
                    if (! $profile->user
                        || ! $profile->user->hasExactRoles(['judge'])
                        || ! $profile->user->hasVerifiedEmail()
                        || ! $profile->password_initialized_at
                        || $profile->max_active_assignments !== null) {
                        throw new AssignmentOperationRejected('invalid_active_judge_prerequisites', 'Todos los jueces activos deben conservar rol exclusivo, capacidad ilimitada y prerrequisitos completos.');
                    }
                }

                $substitute = $substitutes->firstWhere('public_id', $substitutePublicId);
                if (! $substitute) {
                    throw new AssignmentOperationRejected('selected_substitute_invalid', 'Selecciona uno de los dos jueces sustitutos operativos.');
                }

                $duplicateAssignment = JudgeAssignment::query()
                    ->where('judge_profile_id', $substitute->id)
                    ->where('submission_version_id', $original->submission_version_id)
                    ->where('current_slot', 1)
                    ->lockForUpdate()
                    ->first(['id']) !== null;

                if ($duplicateAssignment) {
                    throw new AssignmentOperationRejected('duplicate_substitute_assignment', 'El sustituto ya tiene una asignación vigente para esta propuesta.');
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
                    'judge_profile_id' => $substitute->id,
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

                $original->refresh();
                $lockedConflict->refresh();
                $this->audit->record('assignment.voided_for_conflict', $original, $actor, [
                    'conflict_id' => $lockedConflict->id,
                    'replacement_assignment_id' => $replacement->id,
                ]);
                $this->audit->record('assignment.replacement_created', $replacement, $actor, [
                    'replaces_assignment_id' => $original->id,
                    'submission_version_id' => $original->submission_version_id,
                    'rubric_version_id' => $original->rubric_version_id,
                    'selected_substitute_profile_id' => $substitute->id,
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

            $field = $exception->reasonCode === 'selected_substitute_invalid'
                ? 'substitute_judge_profile'
                : 'replacement';

            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }
}
