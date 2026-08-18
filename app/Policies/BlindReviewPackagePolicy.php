<?php

namespace App\Policies;

use App\Enums\BlindReviewPackageStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeProfileStatus;
use App\Models\BlindReviewPackage;
use App\Models\JudgeAssignment;
use App\Models\User;

class BlindReviewPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminWith($user, 'view blind review packages');
    }

    public function view(User $user, BlindReviewPackage $package): bool
    {
        return $this->isAdminWith($user, 'view blind review packages');
    }

    public function create(User $user): bool
    {
        return $this->isAdminWith($user, 'manage blind review packages');
    }

    public function update(User $user, BlindReviewPackage $package): bool
    {
        return $this->isAdminWith($user, 'manage blind review packages');
    }

    public function consume(User $user, BlindReviewPackage $package, JudgeAssignment $assignment): bool
    {
        return $this->isOperationalJudge($user)
            && $assignment->judge_profile_id === $user->judgeProfile?->id
            && $assignment->status === JudgeAssignmentStatus::Active
            && $assignment->submission_version_id === $package->submission_version_id
            && $package->status === BlindReviewPackageStatus::Active;
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
