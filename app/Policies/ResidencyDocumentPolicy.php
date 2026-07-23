<?php

namespace App\Policies;

use App\Models\ResidencyDocument;
use App\Models\User;

class ResidencyDocumentPolicy
{
    public function download(User $user, ResidencyDocument $document): bool
    {
        if ($document->request->review->submission->user_id === $user->id) {
            return true;
        }

        return $user->can('view residency documents') && $user->can('download residency documents');
    }
}
