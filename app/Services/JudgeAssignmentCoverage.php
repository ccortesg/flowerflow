<?php

namespace App\Services;

use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Models\JudgeAssignment;
use App\Models\SubmissionVersion;
use Illuminate\Support\Collection;

final class JudgeAssignmentCoverage
{
    /**
     * @return array{required:int, covered:int, initial:int, pending_conflicts:int, complete:bool}
     */
    public function summarize(SubmissionVersion $version): array
    {
        $assignments = JudgeAssignment::query()
            ->where('submission_version_id', $version->id)
            ->with('replacementAssignment:id,replaces_assignment_id,status')
            ->get();

        return $this->fromAssignments($assignments);
    }

    /**
     * @param  Collection<int, JudgeAssignment>  $assignments
     * @return array{required:int, covered:int, initial:int, pending_conflicts:int, complete:bool}
     */
    public function fromAssignments(Collection $assignments): array
    {
        $initials = $assignments
            ->filter(fn (JudgeAssignment $assignment): bool => $assignment->type === JudgeAssignmentType::Initial);
        $covered = $initials->filter(function (JudgeAssignment $assignment): bool {
            if ($assignment->status === JudgeAssignmentStatus::Active) {
                return true;
            }

            return $assignment->status === JudgeAssignmentStatus::Voided
                && $assignment->replacementAssignment?->status === JudgeAssignmentStatus::Active;
        })->count();

        $pendingConflicts = $assignments
            ->filter(fn (JudgeAssignment $assignment): bool => $assignment->status === JudgeAssignmentStatus::ConflictDeclared)
            ->count();

        return [
            'required' => 4,
            'covered' => $covered,
            'initial' => $initials->count(),
            'pending_conflicts' => $pendingConflicts,
            'complete' => $covered === 4 && $initials->count() === 4,
        ];
    }
}
