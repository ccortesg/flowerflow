<?php

namespace App\Policies;

use App\Models\EligibilityReview;
use App\Models\User;

class EligibilityReviewPolicy
{
    public function view(User $user, EligibilityReview $review): bool
    {
        return $review->submission()->where('user_id', $user->id)->exists()
            || $user->can('view admissibility reviews');
    }

    public function review(User $user, EligibilityReview $review): bool
    {
        return $user->can('review admissibility');
    }

    public function requestClarification(User $user, EligibilityReview $review): bool
    {
        return $user->can('request clarification');
    }

    public function decide(User $user, EligibilityReview $review): bool
    {
        return $user->can('decide admissibility');
    }
}
