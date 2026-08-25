<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeAssignmentStatus;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelJudgeAssignment
{
    public function __construct(
        private EnsureAssignmentAdministrator $ensureActor,
        private AuditLogger $audit,
    ) {}

    public function execute(JudgeAssignment $assignment, User $actor, string $reason): JudgeAssignment
    {
        $this->ensureActor->execute($actor, 'manage evaluation assignments');

        return DB::transaction(function () use ($assignment, $actor, $reason): JudgeAssignment {
            $locked = JudgeAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== JudgeAssignmentStatus::Active
                || $locked->conflict()->exists()
                || Evaluation::query()->where('judge_assignment_id', $locked->id)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['assignment' => 'Sólo puede cancelarse una asignación activa, sin conflicto y sin evaluación iniciada.']);
            }

            $now = now('UTC');
            DB::table('judge_assignments')->where('id', $locked->id)->update([
                'status' => JudgeAssignmentStatus::Cancelled->value,
                'current_slot' => null,
                'cancelled_by_user_id' => $actor->id,
                'cancellation_reason' => trim($reason),
                'cancelled_at' => $now,
                'updated_at' => $now,
            ]);
            $locked->refresh();
            $this->audit->record('assignment.cancelled', $locked, $actor, [
                'assignment_id' => $locked->id,
                'submission_version_id' => $locked->submission_version_id,
                'judge_profile_id' => $locked->judge_profile_id,
                'rubric_version_id' => $locked->rubric_version_id,
                'from_status' => JudgeAssignmentStatus::Active->value,
                'to_status' => JudgeAssignmentStatus::Cancelled->value,
            ]);

            return $locked;
        }, 3);
    }
}
