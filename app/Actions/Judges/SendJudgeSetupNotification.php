<?php

namespace App\Actions\Judges;

use App\Models\JudgeProfile;
use App\Notifications\JudgeAccountSetupNotification;
use App\Services\AuditLogger;
use App\Services\ResilientMailDispatcher;
use App\Support\MailDispatchStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

final class SendJudgeSetupNotification
{
    public function __construct(
        private AuditLogger $audit,
        private ResilientMailDispatcher $mail,
        private MailDispatchStatus $status,
    ) {}

    public function execute(JudgeProfile $profile): bool
    {
        $profile->loadMissing('user');
        $this->audit->record('judge.setup_email.requested', $profile);

        try {
            $token = Password::broker('users')->createToken($profile->user);
            $sent = $this->mail->notify(
                $profile->user,
                new JudgeAccountSetupNotification($token),
                'La cuenta se creó, pero no pudimos programar el correo de configuración. Puedes reintentarlo desde el detalle del juez.'
            );

            if (! $sent) {
                $this->audit->record('judge.setup_email.failed', $profile, metadata: [
                    'failure_stage' => 'mail_dispatch',
                ]);
            }

            return $sent;
        } catch (Throwable $exception) {
            $this->status->markFailed('La cuenta se creó, pero no pudimos generar el correo de configuración. Puedes reintentarlo desde el detalle del juez.');
            $this->audit->record('judge.setup_email.failed', $profile, metadata: [
                'failure_stage' => 'token_generation',
                'exception_class' => $exception::class,
            ]);
            Log::error('No se pudo generar el enlace de configuración de una cuenta de juez.', [
                'judge_profile_public_id' => $profile->public_id,
                'exception_class' => $exception::class,
            ]);

            return false;
        }
    }
}
