<?php

namespace App\Notifications;

use App\Models\JudgeSetupLink;
use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class JudgeAccountSetupNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(
        public int $setupLinkId,
        #[\SensitiveParameter] public string $token,
    ) {
        $this->configureTransactionalMail();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $link = JudgeSetupLink::query()->findOrFail($this->setupLinkId);
        $data = [
            'actionUrl' => URL::temporarySignedRoute(
                'judge.setup.show',
                $link->expires_at,
                ['setupLink' => $link, 'token' => $this->token],
            ),
            'expiresAt' => $link->expires_at->timezone(config('flowerflow.timezone'))->format('d/m/Y H:i'),
            'userName' => $notifiable->name,
        ];

        return (new MailMessage)
            ->subject('Configura tu acceso de juez · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-account-setup', $data)
            ->text('mail.judge-account-setup-text', $data);
    }
}
