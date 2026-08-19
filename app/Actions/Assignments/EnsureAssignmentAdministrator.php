<?php

namespace App\Actions\Assignments;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class EnsureAssignmentAdministrator
{
    /** @throws AuthorizationException */
    public function execute(User $actor, string $permission): void
    {
        if (! $actor->hasExactRoles(['admin']) || ! $actor->can($permission)) {
            throw new AuthorizationException;
        }
    }
}
