<?php

namespace App\Services;

use App\Enums\JudgeProfileStatus;
use App\Exceptions\JudgeSetupLinkRejected;
use App\Models\JudgeSetupLink;

final class JudgeSetupLinkValidator
{
    public function assertValid(JudgeSetupLink $link, string $token): void
    {
        $link->loadMissing('judgeProfile.user.roles');
        $profile = $link->judgeProfile;
        $judge = $profile?->user;

        if ($link->active_slot !== 1 || $link->consumed_at !== null || $link->invalidated_at !== null) {
            throw new JudgeSetupLinkRejected('setup_link_not_active', 'Este enlace ya no está vigente.');
        }
        if ($link->expires_at->isPast()) {
            throw new JudgeSetupLinkRejected('setup_link_expired', 'Este enlace de configuración ya venció.');
        }
        if (! hash_equals($link->token_hash, hash('sha256', $token))) {
            throw new JudgeSetupLinkRejected('setup_link_token_invalid', 'El enlace de configuración no es válido.');
        }
        if (! $profile || ! $judge
            || $profile->status !== JudgeProfileStatus::PendingSetup
            || ! $judge->hasExactRoles(['judge'])
            || ! hash_equals($link->email_fingerprint, CommunicationMessageRegistry::recipientFingerprint((string) $judge->email))) {
            throw new JudgeSetupLinkRejected('setup_link_context_changed', 'La cuenta cambió y este enlace ya no puede utilizarse.');
        }
    }
}
