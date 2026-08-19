<?php

namespace App\Actions\Assignments;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class EnsureActiveJudgeAssignmentActor
{
    /** @throws AuthorizationException */
    public function execute(User $actor): JudgeProfile
    {
        $profile = $actor->judgeProfile()->first();

        if (! $actor->hasExactRoles(['judge'])
            || ! $actor->can('declare own evaluation conflicts')
            || ! $actor->hasVerifiedEmail()
            || ! $profile
            || $profile->status !== JudgeProfileStatus::Active
            || ! $profile->password_initialized_at) {
            throw new AuthorizationException;
        }

        return $profile;
    }
}
