<?php

namespace App\Actions\Judges;

use App\Models\JudgeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecoverJudgeTwoFactor
{
    public function __construct(
        private EnsureJudgeAdministrationActor $ensureActor,
        private RevokeUserSessions $revokeSessions,
        private AuditLogger $audit,
        private SendJudgeAccountStatusNotification $sendNotification,
    ) {}

    public function execute(JudgeProfile $profile, User $actor, string $reason): JudgeProfile
    {
        $this->ensureActor->execute($actor, 'recover judge two factor');

        $recovered = DB::transaction(function () use ($profile, $actor, $reason): JudgeProfile {
            $lockedProfile = JudgeProfile::query()->whereKey($profile->getKey())->lockForUpdate()->firstOrFail();
            $judge = User::query()->whereKey($lockedProfile->user_id)->lockForUpdate()->firstOrFail();
            if (! $judge->hasExactRoles(['judge'])) {
                throw ValidationException::withMessages(['judge' => 'La cuenta no conserva el rol exclusivo de juez.']);
            }
            if (! $judge->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['judge' => 'La recuperación 2FA requiere un correo verificado para notificar la operación.']);
            }

            $hadTwoFactor = filled($judge->two_factor_secret)
                || filled($judge->two_factor_recovery_codes)
                || filled($judge->two_factor_confirmed_at);
            $revokedSessions = $this->revokeSessions->execute($judge);
            $judge->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();

            $this->audit->record('judge.two_factor_recovered', $lockedProfile, $actor, [
                'reason' => trim($reason),
                'had_two_factor' => $hadTwoFactor,
                'revoked_sessions' => $revokedSessions,
            ]);

            return $lockedProfile->refresh();
        });

        $this->sendNotification->execute(
            $recovered,
            'two_factor_recovered',
            $actor,
            verifiedOnly: true,
        );

        return $recovered;
    }
}
