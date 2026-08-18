<?php

namespace App\Actions\Judges;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class SynchronizeJudgeProfileActivation
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, ?User $actor = null): ?JudgeProfile
    {
        return DB::transaction(function () use ($user, $actor): ?JudgeProfile {
            $lockedUser = User::query()->lockForUpdate()->find($user->getKey());
            if (! $lockedUser?->hasExactRoles(['judge'])) {
                return null;
            }

            $profile = JudgeProfile::query()->where('user_id', $lockedUser->id)->lockForUpdate()->first();
            if (! $profile || $profile->status === JudgeProfileStatus::Suspended) {
                return $profile;
            }

            $nextStatus = $profile->password_initialized_at && $lockedUser->email_verified_at
                ? JudgeProfileStatus::Active
                : JudgeProfileStatus::PendingSetup;

            if ($profile->status === $nextStatus) {
                return $profile;
            }

            $previousStatus = $profile->status;
            $profile->forceFill([
                'status' => $nextStatus,
                'activated_at' => $nextStatus === JudgeProfileStatus::Active
                    ? ($profile->activated_at ?? now('UTC'))
                    : $profile->activated_at,
            ])->save();

            $this->audit->record('judge.profile_status_changed', $profile, $actor ?? $lockedUser, [
                'from_status' => $previousStatus->value,
                'to_status' => $nextStatus->value,
                'reason' => 'prerequisites_synchronized',
            ]);

            return $profile->refresh();
        });
    }
}
