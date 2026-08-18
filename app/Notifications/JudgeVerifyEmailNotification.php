<?php

namespace App\Notifications;

use App\Support\ConfiguresTransactionalMail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;

class JudgeVerifyEmailNotification extends VerifyEmail implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct()
    {
        $this->configureTransactionalMail();
    }

    public function toMail($notifiable): MailMessage
    {
        $data = [
            'actionUrl' => $this->verificationUrl($notifiable),
            'userName' => $notifiable->name,
        ];

        return (new MailMessage)
            ->subject('Verifica tu correo de juez · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.judge-verify-email', $data)
            ->text('mail.judge-verify-email-text', $data);
    }
}
