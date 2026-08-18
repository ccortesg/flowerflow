<?php

namespace App\Policies;

use App\Models\JudgeProfile;
use App\Models\User;

class JudgeProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminWith($user, 'view judges');
    }

    public function view(User $user, JudgeProfile $profile): bool
    {
        return $this->isAdminWith($user, 'view judges');
    }

    public function create(User $user): bool
    {
        return $this->isAdminWith($user, 'manage judges');
    }

    public function manage(User $user, JudgeProfile $profile): bool
    {
        return $this->isAdminWith($user, 'manage judges');
    }

    public function recoverTwoFactor(User $user, JudgeProfile $profile): bool
    {
        return $this->isAdminWith($user, 'recover judge two factor');
    }

    private function isAdminWith(User $user, string $permission): bool
    {
        return $user->hasExactRoles(['admin']) && $user->can($permission);
    }
}
