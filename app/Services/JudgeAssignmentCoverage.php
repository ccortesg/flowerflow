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
     * @return array{active:int, initial:int, pending_conflicts:int, cancelled:int, replaced:int}
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
     * @return array{active:int, initial:int, pending_conflicts:int, cancelled:int, replaced:int}
     */
    public function fromAssignments(Collection $assignments): array
    {
        return [
            'active' => $assignments->where('status', JudgeAssignmentStatus::Active)->count(),
            'initial' => $assignments->where('type', JudgeAssignmentType::Initial)->count(),
            'pending_conflicts' => $assignments->where('status', JudgeAssignmentStatus::ConflictDeclared)->count(),
            'cancelled' => $assignments->where('status', JudgeAssignmentStatus::Cancelled)->count(),
            'replaced' => $assignments->where('status', JudgeAssignmentStatus::Voided)->count(),
        ];
    }
}
