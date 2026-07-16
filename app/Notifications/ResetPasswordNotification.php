<?php

namespace App\Notifications;

use App\Support\ConfiguresTransactionalMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use ConfiguresTransactionalMail, Queueable;

    public function __construct(#[\SensitiveParameter] $token)
    {
        parent::__construct($token);
        $this->configureTransactionalMail();
    }

    public function toMail($notifiable): MailMessage
    {
        $data = [
            'actionUrl' => $this->resetUrl($notifiable),
            'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            'userName' => $notifiable->name,
        ];

        return (new MailMessage)
            ->subject('Restablece tu contraseña · Flower Flow')
            ->replyTo(config('flowerflow.mail.reply_to'), config('flowerflow.mail.reply_to_name'))
            ->view('mail.reset-password', $data)
            ->text('mail.reset-password-text', $data);
    }
}
