<?php

namespace App\Actions\Judges;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class EnsureJudgeAdministrationActor
{
    /** @throws AuthorizationException */
    public function execute(User $actor, string $permission): void
    {
        if (! $actor->hasExactRoles(['admin']) || ! $actor->can($permission)) {
            throw new AuthorizationException;
        }
    }
}
