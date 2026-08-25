<?php

namespace App\Policies;

use App\Models\RubricVersion;
use App\Models\User;

class RubricVersionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdminWith($user, 'view evaluation rubrics');
    }

    public function view(User $user, RubricVersion $rubric): bool
    {
        return $this->isAdminWith($user, 'view evaluation rubrics');
    }

    public function create(User $user): bool
    {
        return $this->isAdminWith($user, 'manage evaluation rubrics');
    }

    public function update(User $user, RubricVersion $rubric): bool
    {
        return false;
    }

    public function activate(User $user, RubricVersion $rubric): bool
    {
        return false;
    }

    private function isAdminWith(User $user, string $permission): bool
    {
        return $user->hasExactRoles(['admin']) && $user->can($permission);
    }
}
