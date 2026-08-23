<?php

namespace App\Services;

use App\Enums\CommunicationAttemptSource;
use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Jobs\DeliverCommunication;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationDeliveryAttempt;
use App\Models\SubmissionReminder;
use App\Models\User;
use App\Support\MailDispatchStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final class ResilientMailDispatcher
{
    public function __construct(
        private MailDispatchStatus $status,
        private CommunicationMessageRegistry $registry,
        private AuditLogger $audit,
    ) {}

    public function notify(User $recipient, Notification $notification, string $warning, ?string $sourceEventKey = null): bool
    {
        if (config('flowerflow.flags.communication_ledger')) {
            return $this->recordAndDispatch($recipient, $notification, $warning, $sourceEventKey);
        }

        try {
            $recipient->notify($notification);

            return true;
        } catch (Throwable $exception) {
            return $this->recordFailure($recipient, $notification::class, $warning, $exception);
        }
    }

    public function queue(User $recipient, Mailable $mailable, string $warning, ?string $sourceEventKey = null): bool
    {
        if (config('flowerflow.flags.communication_ledger')) {
            return $this->recordAndDispatch($recipient, $mailable, $warning, $sourceEventKey);
        }

        try {
            Mail::to($recipient)->queue($mailable);

            return true;
        } catch (Throwable $exception) {
            return $this->recordFailure($recipient, $mailable::class, $warning, $exception);
        }
    }

    public function recordAndDispatch(
        User $recipient,
        Mailable|Notification $message,
        string $warning,
        ?string $sourceEventKey = null,
    ): bool {
        try {
            $definition = $this->registry->describe($recipient, $message);
            $fingerprint = CommunicationMessageRegistry::recipientFingerprint((string) $recipient->email);
            $sourceEventKey = hash_hmac(
                'sha256',
                $sourceEventKey ?? (string) Str::ulid(),
                (string) config('app.key'),
            );
            $templateVersion = (string) config('flowerflow.communication_ledger.template_version');
            $idempotencyKey = hash('sha256', implode('|', [
                $definition['type']->value,
                $fingerprint,
                $sourceEventKey,
                $templateVersion,
            ]));

            try {
                $delivery = DB::transaction(function () use (
                    $recipient,
                    $definition,
                    $fingerprint,
                    $sourceEventKey,
                    $templateVersion,
                    $idempotencyKey,
                ): CommunicationDelivery {
                    $existing = CommunicationDelivery::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                    if ($existing) {
                        return $existing;
                    }

                    $now = now('UTC');
                    $delivery = new CommunicationDelivery;
                    $delivery->forceFill([
                        'idempotency_key' => $idempotencyKey,
                        'notification_type' => $definition['type'],
                        'variant' => $definition['variant'],
                        'template_version' => $templateVersion,
                        'source_event_key' => $sourceEventKey,
                        'channel' => 'email',
                        'recipient_user_id' => $recipient->id,
                        'recipient_address' => (string) $recipient->email,
                        'recipient_mask' => CommunicationMessageRegistry::maskAddress((string) $recipient->email),
                        'recipient_fingerprint' => $fingerprint,
                        'context' => $definition['context'],
                        'related_type' => $definition['related_type'],
                        'related_id' => $definition['related_id'],
                        'status' => CommunicationDeliveryStatus::Queued,
                        'queue_connection' => config('flowerflow.mail.queue_connection'),
                        'queue' => config('flowerflow.mail.queue'),
                        'queued_at' => $now,
                    ])->save();

                    if ($definition['type']->value === 'submission.draft_reminder') {
                        SubmissionReminder::query()->whereKey($definition['related_id'])->update([
                            'communication_delivery_id' => $delivery->id,
                        ]);
                    }

                    $attempt = new CommunicationDeliveryAttempt;
                    $attempt->forceFill([
                        'communication_delivery_id' => $delivery->id,
                        'attempt_number' => 1,
                        'source' => CommunicationAttemptSource::Automatic,
                        'status' => CommunicationAttemptStatus::Queued,
                        'queue' => $delivery->queue,
                        'queued_at' => $now,
                    ])->save();
                    $this->audit->record('communication_delivery.queued', $delivery, metadata: [
                        'delivery_id' => $delivery->id,
                        'notification_type' => $delivery->notification_type->value,
                        'attempt_number' => 1,
                        'transition' => 'created_to_queued',
                    ]);

                    return $delivery;
                }, 3);
            } catch (UniqueConstraintViolationException) {
                $delivery = CommunicationDelivery::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
            }

            if ($delivery->wasRecentlyCreated && $delivery->status === CommunicationDeliveryStatus::Queued) {
                DeliverCommunication::dispatch($delivery->id);
            }

            return true;
        } catch (Throwable $exception) {
            if (isset($delivery) && $delivery instanceof CommunicationDelivery) {
                $this->markEnqueueFailed($delivery, $exception);
                app(CommunicationDeliveryStateSynchronizer::class)->sync($delivery->fresh());
            }

            return $this->recordFailure($recipient, $message::class, $warning, $exception);
        }
    }

    private function markEnqueueFailed(CommunicationDelivery $delivery, Throwable $exception): void
    {
        DB::transaction(function () use ($delivery, $exception): void {
            $locked = CommunicationDelivery::query()->lockForUpdate()->find($delivery->id);
            if (! $locked || $locked->status !== CommunicationDeliveryStatus::Queued) {
                return;
            }
            $now = now('UTC');
            $code = class_basename($exception);
            $locked->attempts()->where('status', CommunicationAttemptStatus::Queued->value)->update([
                'status' => CommunicationAttemptStatus::Failed->value,
                'failure_stage' => 'enqueue',
                'failure_code' => $code,
                'finished_at' => $now,
                'updated_at' => $now,
            ]);
            $locked->forceFill([
                'status' => CommunicationDeliveryStatus::Failed,
                'failure_stage' => 'enqueue',
                'failure_code' => $code,
                'failed_at' => $now,
                'context_expires_at' => $now->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days')),
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $this->audit->record('communication_delivery.failed', $locked, metadata: [
                'delivery_id' => $locked->id,
                'notification_type' => $locked->notification_type->value,
                'attempt_number' => 1,
                'transition' => 'queued_to_failed',
                'reason_code' => $code,
            ]);
        }, 3);
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
