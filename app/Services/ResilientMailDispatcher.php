<?php

namespace App\Services;

use App\Models\User;
use App\Support\MailDispatchStatus;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class ResilientMailDispatcher
{
    public function __construct(private MailDispatchStatus $status) {}

    public function notify(User $recipient, Notification $notification, string $warning): bool
    {
        try {
            $recipient->notify($notification);

            return true;
        } catch (Throwable $exception) {
            return $this->recordFailure($recipient, $notification::class, $warning, $exception);
        }
    }

    public function queue(User $recipient, Mailable $mailable, string $warning): bool
    {
        try {
            Mail::to($recipient)->queue($mailable);

            return true;
        } catch (Throwable $exception) {
            return $this->recordFailure($recipient, $mailable::class, $warning, $exception);
        }
    }

    private function recordFailure(User $recipient, string $mailType, string $warning, Throwable $exception): bool
    {
        $this->status->markFailed($warning);

        Log::error('No se pudo programar un correo transaccional.', [
            'mail_type' => $mailType,
            'user_public_id' => $recipient->public_id,
            'exception_class' => $exception::class,
        ]);

        return false;
    }
}
