<?php

namespace App\Policies;

use App\Enums\JudgeConflictStatus;
use App\Models\JudgeConflict;
use App\Models\User;

class JudgeConflictPolicy
{
    public function view(User $user, JudgeConflict $conflict): bool
    {
        return ($user->hasExactRoles(['admin']) && $user->can('view evaluation assignments'))
            || ($user->hasExactRoles(['judge'])
                && $conflict->declared_by_judge_profile_id === $user->judgeProfile?->id);
    }

    public function resolve(User $user, JudgeConflict $conflict): bool
    {
        return in_array($conflict->status, [JudgeConflictStatus::Declared, JudgeConflictStatus::ResolvedReassigned], true)
            && $user->hasExactRoles(['admin'])
            && $user->can('resolve evaluation conflicts');
    }
}
