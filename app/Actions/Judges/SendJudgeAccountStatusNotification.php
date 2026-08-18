<?php

namespace App\Actions\Judges;

use App\Models\JudgeProfile;
use App\Models\User;
use App\Notifications\JudgeAccountStatusNotification;
use App\Services\AuditLogger;
use App\Services\ResilientMailDispatcher;

final class SendJudgeAccountStatusNotification
{
    public function __construct(
        private AuditLogger $audit,
        private ResilientMailDispatcher $mail,
    ) {}

    public function execute(JudgeProfile $profile, string $event, ?User $actor, bool $verifiedOnly = false): bool
    {
        $profile->loadMissing('user');
        if ($verifiedOnly && ! $profile->user->hasVerifiedEmail()) {
            return false;
        }

        $sent = $this->mail->notify(
            $profile->user,
            new JudgeAccountStatusNotification($event),
            'La operación se completó, pero no pudimos programar su notificación por correo.'
        );

        $this->audit->record(
            $sent ? 'judge.account_notification.requested' : 'judge.account_notification.failed',
            $profile,
            $actor,
            ['event' => $event]
        );

        return $sent;
    }
}
