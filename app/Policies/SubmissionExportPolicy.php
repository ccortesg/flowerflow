<?php

namespace App\Policies;

use App\Models\SubmissionExport;
use App\Models\User;

class SubmissionExportPolicy
{
    public function create(User $user): bool
    {
        return $user->can('export submissions');
    }

    public function view(User $user, SubmissionExport $submissionExport): bool
    {
        return $submissionExport->requested_by_user_id === $user->id
            && $user->can('export submissions');
    }

    public function download(User $user, SubmissionExport $submissionExport): bool
    {
        return $this->view($user, $submissionExport);
    }
}
