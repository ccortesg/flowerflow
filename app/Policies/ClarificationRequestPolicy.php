<?php

namespace App\Policies;

use App\Enums\ClarificationStatus;
use App\Models\ClarificationRequest;
use App\Models\User;

class ClarificationRequestPolicy
{
    public function view(User $user, ClarificationRequest $clarification): bool
    {
        return $clarification->review->submission->user_id === $user->id
            || $user->can('view admissibility reviews');
    }

    public function respond(User $user, ClarificationRequest $clarification): bool
    {
        return $clarification->review->submission->user_id === $user->id
            && $clarification->status === ClarificationStatus::Open;
    }

    public function close(User $user, ClarificationRequest $clarification): bool
    {
        return $user->can('request clarification');
    }
}
