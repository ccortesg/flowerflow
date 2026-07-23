<?php

namespace App\Policies;

use App\Models\ClarificationResponseFile;
use App\Models\User;

class ClarificationResponseFilePolicy
{
    public function download(User $user, ClarificationResponseFile $file): bool
    {
        return $file->response->clarification->review->submission->user_id === $user->id
            || $user->can('view admissibility reviews');
    }
}
