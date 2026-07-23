<?php

namespace App\Actions;

use App\Enums\EligibilityReviewStatus;
use App\Models\EligibilityReview;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;

final class EnsureEligibilityReview
{
    public function execute(Submission $submission, SubmissionVersion $version, ?User $actor = null): EligibilityReview
    {
        $review = EligibilityReview::query()->firstOrCreate(
            ['submission_id' => $submission->id],
            [
                'submission_version_id' => $version->id,
                'status' => EligibilityReviewStatus::Pending,
            ]
        );

        if ($review->wasRecentlyCreated) {
            $review->events()->create([
                'actor_user_id' => $actor?->id,
                'event' => 'review_created',
                'to_status' => EligibilityReviewStatus::Pending->value,
                'created_at' => now('UTC'),
            ]);
        }

        return $review;
    }
}
