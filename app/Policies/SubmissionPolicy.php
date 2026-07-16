<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id || $user->hasAnyRole(['admin', 'reviewer']);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id && $submission->isDraft();
    }

    public function submit(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id && in_array($submission->status, ['draft', 'submitted'], true);
    }
}
