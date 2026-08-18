<?php

namespace App\Notifications;

use App\Support\ConfiguresTransactionalMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

class JudgeAccountStatusNotification extends Notification implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(public readonly string $event)
    {
        if (! in_array($event, ['suspended', 'reactivated', 'two_factor_recovered'], true)) {
            throw new InvalidArgumentException('Unsupported judge account notification event.');
        }

        $this->configureTransactionalMail();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subjects = [
            'suspended' => 'Tu acceso de juez fue suspendido · Flower Flow',
            'reactivated' => 'Tu acceso de juez fue reactivado · Flower Flow',
            'two_factor_recovered' => 'Se recuperó el acceso 2FA de tu cuenta · Flower Flow',
        ];

        $data = ['event' => $this->event, 'userName' => $notifiable->name];

        return (new MailMessage)
            ->subject($subjects[$this->event])
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-account-status', $data)
            ->text('mail.judge-account-status-text', $data);
    }
}
