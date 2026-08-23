<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id || $user->can('view submissions');
    }

    public function downloadFile(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id
            || ($user->can('view submissions') && $user->can('download private files'));
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id && $submission->isDraft();
    }

    public function submit(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id && in_array($submission->status, ['draft', 'submitted'], true);
    }

    public function sendReminder(User $user, Submission $submission): bool
    {
        return $user->hasExactRoles(['admin'])
            && $user->can('send submission reminders')
            && $submission->isDraft();
    }

    public function administrativelyFinalize(User $user, Submission $submission): bool
    {
        return $user->hasExactRoles(['admin'])
            && $user->can('administratively finalize submissions')
            && in_array($submission->status, ['draft', 'submitted'], true);
    }
}
