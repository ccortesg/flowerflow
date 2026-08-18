<?php

namespace App\Actions\Judges;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SuspendJudge
{
    public function __construct(
        private EnsureJudgeAdministrationActor $ensureActor,
        private RevokeUserSessions $revokeSessions,
        private AuditLogger $audit,
        private SendJudgeAccountStatusNotification $sendNotification,
    ) {}

    public function execute(JudgeProfile $profile, User $actor, string $reason): JudgeProfile
    {
        $this->ensureActor->execute($actor, 'manage judges');

        $suspended = DB::transaction(function () use ($profile, $actor, $reason): JudgeProfile {
            $lockedProfile = JudgeProfile::query()->whereKey($profile->getKey())->lockForUpdate()->firstOrFail();
            $judge = User::query()->whereKey($lockedProfile->user_id)->lockForUpdate()->firstOrFail();
            if (! $judge->hasExactRoles(['judge'])) {
                throw ValidationException::withMessages(['judge' => 'La cuenta no conserva el rol exclusivo de juez.']);
            }
            if ($lockedProfile->status === JudgeProfileStatus::Suspended) {
                throw ValidationException::withMessages(['judge' => 'La cuenta ya está suspendida.']);
            }

            $revokedSessions = $this->revokeSessions->execute($judge);
            $previousStatus = $lockedProfile->status;
            $lockedProfile->forceFill([
                'status' => JudgeProfileStatus::Suspended,
                'suspended_at' => now('UTC'),
                'suspended_by_user_id' => $actor->id,
                'suspension_reason' => trim($reason),
            ])->save();
            $this->audit->record('judge.suspended', $lockedProfile, $actor, [
                'from_status' => $previousStatus->value,
                'to_status' => JudgeProfileStatus::Suspended->value,
                'reason' => trim($reason),
                'revoked_sessions' => $revokedSessions,
            ]);

            return $lockedProfile->refresh();
        });

        $this->sendNotification->execute($suspended, 'suspended', $actor);

        return $suspended;
    }
}
