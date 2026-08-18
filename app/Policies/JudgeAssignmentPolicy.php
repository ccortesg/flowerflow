<?php

namespace App\Policies;

use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeProfileStatus;
use App\Models\JudgeAssignment;
use App\Models\User;

class JudgeAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminWith($user, 'view evaluation assignments')
            || $this->isOperationalJudge($user);
    }

    public function view(User $user, JudgeAssignment $assignment): bool
    {
        if ($this->isAdminWith($user, 'view evaluation assignments')) {
            return true;
        }

        return $this->isOperationalJudge($user)
            && $assignment->judge_profile_id === $user->judgeProfile?->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdminWith($user, 'manage evaluation assignments');
    }

    public function declareConflict(User $user, JudgeAssignment $assignment): bool
    {
        return $this->isOperationalJudge($user)
            && $user->can('declare own evaluation conflicts')
            && $assignment->judge_profile_id === $user->judgeProfile?->id
            && in_array($assignment->status, [JudgeAssignmentStatus::Active, JudgeAssignmentStatus::ConflictDeclared], true);
    }

    private function isAdminWith(User $user, string $permission): bool
    {
        return $user->hasExactRoles(['admin']) && $user->can($permission);
    }

    private function isOperationalJudge(User $user): bool
    {
        return $user->hasExactRoles(['judge'])
            && $user->hasVerifiedEmail()
            && $user->judgeProfile?->status === JudgeProfileStatus::Active
            && $user->judgeProfile?->password_initialized_at !== null;
    }
}
