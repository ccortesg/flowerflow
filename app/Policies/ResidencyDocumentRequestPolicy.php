<?php

namespace App\Policies;

use App\Enums\ResidencyVerificationStatus;
use App\Models\ResidencyDocumentRequest;
use App\Models\User;

class ResidencyDocumentRequestPolicy
{
    public function view(User $user, ResidencyDocumentRequest $request): bool
    {
        return $request->review->submission->user_id === $user->id
            || $user->can('view residency documents');
    }

    public function upload(User $user, ResidencyDocumentRequest $request): bool
    {
        return $request->review->submission->user_id === $user->id
            && in_array($request->status, [ResidencyVerificationStatus::Requested, ResidencyVerificationStatus::Rejected], true);
    }

    public function review(User $user, ResidencyDocumentRequest $request): bool
    {
        return $user->can('view residency documents') && $user->can('review admissibility');
    }
}
