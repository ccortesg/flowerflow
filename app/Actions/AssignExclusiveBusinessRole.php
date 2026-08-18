<?php

namespace App\Actions;

use App\Enums\BusinessRole;
use App\Models\User;
use DomainException;

class AssignExclusiveBusinessRole
{
    public function execute(User $user, BusinessRole $role): User
    {
        $this->assertCanAssign($user, $role);

        if ($user->roles()->count() === 0) {
            $user->assignRole($role->value);
        }

        return $user->refresh();
    }

    public function assertCanAssign(User $user, BusinessRole $role): void
    {
        $currentRoles = $user->getRoleNames()->values();

        if ($currentRoles->count() > 1) {
            throw new DomainException('The account has an invalid multiple-role assignment.');
        }

        if ($currentRoles->count() === 1 && $currentRoles->first() !== $role->value) {
            throw new DomainException('Business roles are exclusive and cannot be replaced implicitly.');
        }
    }
}
