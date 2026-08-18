<?php

namespace App\Actions\Judges;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReactivateJudge
{
    public function __construct(
        private EnsureJudgeAdministrationActor $ensureActor,
        private AuditLogger $audit,
        private SendJudgeAccountStatusNotification $sendNotification,
    ) {}

    public function execute(JudgeProfile $profile, User $actor, string $reason): JudgeProfile
    {
        $this->ensureActor->execute($actor, 'manage judges');

        $reactivated = DB::transaction(function () use ($profile, $actor, $reason): JudgeProfile {
            $lockedProfile = JudgeProfile::query()->whereKey($profile->getKey())->lockForUpdate()->firstOrFail();
            $judge = User::query()->whereKey($lockedProfile->user_id)->lockForUpdate()->firstOrFail();
            if (! $judge->hasExactRoles(['judge'])) {
                throw ValidationException::withMessages(['judge' => 'La cuenta no conserva el rol exclusivo de juez.']);
            }
            if ($lockedProfile->status !== JudgeProfileStatus::Suspended) {
                throw ValidationException::withMessages(['judge' => 'Sólo una cuenta suspendida puede reactivarse.']);
            }

            $nextStatus = $lockedProfile->password_initialized_at && $judge->email_verified_at
                ? JudgeProfileStatus::Active
                : JudgeProfileStatus::PendingSetup;
            $lockedProfile->forceFill([
                'status' => $nextStatus,
                'activated_at' => $nextStatus === JudgeProfileStatus::Active
                    ? ($lockedProfile->activated_at ?? now('UTC'))
                    : $lockedProfile->activated_at,
                'reactivated_at' => now('UTC'),
                'reactivated_by_user_id' => $actor->id,
            ])->save();
            $this->audit->record('judge.reactivated', $lockedProfile, $actor, [
                'from_status' => JudgeProfileStatus::Suspended->value,
                'to_status' => $nextStatus->value,
                'reason' => trim($reason),
            ]);

            return $lockedProfile->refresh();
        });

        $this->sendNotification->execute($reactivated, 'reactivated', $actor);

        return $reactivated;
    }
}
