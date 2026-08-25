<?php

namespace App\Services;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;

final class AdministrativeJudgeEligibility
{
    public function isAssignable(JudgeProfile $profile): bool
    {
        return $this->rejectionCode($profile) === null;
    }

    public function isOperational(JudgeProfile $profile): bool
    {
        return $this->isAssignable($profile)
            && $profile->status === JudgeProfileStatus::Active;
    }

    public function rejectionCode(JudgeProfile $profile): ?string
    {
        $profile->loadMissing('user.roles');

        if (! $profile->user || ! $profile->user->hasExactRoles(['judge'])) {
            return 'judge_role_invalid';
        }
        if ($profile->max_active_assignments !== null) {
            return 'judge_capacity_contract_invalid';
        }
        if ($profile->status === JudgeProfileStatus::Suspended) {
            return 'judge_suspended';
        }
        if ($profile->status === JudgeProfileStatus::Active) {
            return $profile->user->hasVerifiedEmail() && $profile->password_initialized_at !== null
                ? null
                : 'active_judge_setup_incomplete';
        }
        if ($profile->status === JudgeProfileStatus::PendingSetup) {
            return ! $profile->user->hasVerifiedEmail() || $profile->password_initialized_at === null
                ? null
                : 'pending_judge_already_operational';
        }

        return 'judge_status_invalid';
    }

    public function sortRank(JudgeProfile $profile): int
    {
        return $profile->status === JudgeProfileStatus::Active ? 0 : 1;
    }
}
