<?php

namespace App\Policies;

use App\Enums\BlindReviewPackageStatus;
use App\Models\BlindReviewPackageFile;
use App\Models\JudgeAssignment;
use App\Models\User;

class BlindReviewPackageFilePolicy
{
    public function download(User $user, BlindReviewPackageFile $file, JudgeAssignment $assignment): bool
    {
        $file->loadMissing('package');

        return (new BlindReviewPackagePolicy)->consume($user, $file->package, $assignment)
            && $file->blind_review_package_id === $file->package->id
            && $file->status === BlindReviewPackageStatus::Active
            && $file->package->submission_version_id === $assignment->submission_version_id;
    }
}
