<?php

namespace App\Actions\BlindReview;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class EnsureBlindReviewAdministrator
{
    /** @throws AuthorizationException */
    public function execute(User $actor, string $permission): void
    {
        if (! $actor->hasExactRoles(['admin']) || ! $actor->can($permission)) {
            throw new AuthorizationException;
        }
    }
}
