<?php

namespace App\Jobs;

use App\Enums\CommunicationAttemptSource;
use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Exceptions\CommunicationCancelledException;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationDeliveryAttempt;
use App\Services\AuditLogger;
use App\Services\CommunicationDeliveryStateSynchronizer;
use App\Services\CommunicationMessageRegistry;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeliverCommunication implements ShouldBeEncrypted, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries;

    public int $timeout;

    /** @var array<int, int> */
    public array $backoff;

    public function __construct(public int $communicationDeliveryId)
    {
        $this->tries = (int) config('flowerflow.mail.tries');
        $this->timeout = (int) config('flowerflow.mail.timeout');
        $this->backoff = config('flowerflow.mail.backoff');
        $this->onConnection(config('flowerflow.mail.queue_connection'));
        $this->onQueue(config('flowerflow.mail.queue'));
    }

    public function handle(
        CommunicationMessageRegistry $registry,
        CommunicationDeliveryStateSynchronizer $synchronizer,
        AuditLogger $audit,
    ): void {
        if (! config('flowerflow.flags.communication_ledger')) {
            return;
        }

        $attempt = $this->beginAttempt($audit);
        if (! $attempt) {
            return;
        }

        $delivery = CommunicationDelivery::query()->findOrFail($this->communicationDeliveryId);
        $synchronizer->sync($delivery);

        try {
            $registry->send($delivery);
        } catch (CommunicationCancelledException $exception) {
            $delivery = $this->finishCancelled($attempt->id, $exception->reasonCode, $audit);
            $synchronizer->sync($delivery);

            return;
        } catch (Throwable $exception) {
            [$delivery, $shouldRetry] = $this->finishFailure($attempt->id, $exception, $audit);
            $synchronizer->sync($delivery);
            if ($shouldRetry) {
                throw $exception;
            }

            return;
        }

        try {
            $delivery = $this->finishSent($attempt->id, $audit);
        } catch (Throwable $exception) {
            $delivery = $this->finishUnknown($attempt->id, 'state_persistence_failed', $audit);
            $synchronizer->sync($delivery);
            throw $exception;
        }
        $synchronizer->sync($delivery);
    }

    public function failed(Throwable $exception): void
    {
        $delivery = CommunicationDelivery::query()->find($this->communicationDeliveryId);
        if (! $delivery || in_array($delivery->status, [
            CommunicationDeliveryStatus::Sent,
            CommunicationDeliveryStatus::Cancelled,
            CommunicationDeliveryStatus::Failed,
            CommunicationDeliveryStatus::Unknown,
        ], true)) {
            return;
        }

        if ($delivery->status === CommunicationDeliveryStatus::Processing) {
            $attempt = $delivery->attempts()->where('status', CommunicationAttemptStatus::Processing->value)->latest('attempt_number')->first();
            if ($attempt) {
                $delivery = $this->finishUnknown($attempt->id, 'queue_exhausted_during_processing', app(AuditLogger::class));
                app(CommunicationDeliveryStateSynchronizer::class)->sync($delivery);
            }

            return;
        }

        DB::transaction(function () use ($exception): void {
            $locked = CommunicationDelivery::query()->lockForUpdate()->find($this->communicationDeliveryId);
            if (! $locked || $locked->status !== CommunicationDeliveryStatus::Queued) {
                return;
            }
            $attempt = $locked->attempts()->where('status', CommunicationAttemptStatus::Queued->value)->latest('attempt_number')->first();
            $now = now('UTC');
            $attempt?->forceFill([
                'status' => CommunicationAttemptStatus::Failed,
                'failure_stage' => 'queue',
                'failure_code' => class_basename($exception),
                'finished_at' => $now,
            ])->save();
            $locked->forceFill([
                'status' => CommunicationDeliveryStatus::Failed,
                'failure_stage' => 'queue',
                'failure_code' => class_basename($exception),
                'failed_at' => $now,
                'context_expires_at' => $now->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days')),
                'lock_version' => $locked->lock_version + 1,
            ])->save();
        }, 3);
    }

    private function beginAttempt(AuditLogger $audit): ?CommunicationDeliveryAttempt
    {
        return DB::transaction(function () use ($audit): ?CommunicationDeliveryAttempt {
            $delivery = CommunicationDelivery::query()->lockForUpdate()->find($this->communicationDeliveryId);
            if (! $delivery || $delivery->status !== CommunicationDeliveryStatus::Queued) {
                return null;
            }

            $attempt = $delivery->attempts()
                ->where('status', CommunicationAttemptStatus::Queued->value)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->first();
            if (! $attempt) {
                return null;
            }

            $now = now('UTC');
            $delivery->attempts()
                ->where('status', CommunicationAttemptStatus::Queued->value)
                ->whereKeyNot($attempt->id)
                ->update([
                    'status' => CommunicationAttemptStatus::Cancelled->value,
                    'failure_stage' => 'queue',
                    'failure_code' => 'superseded_by_newer_attempt',
                    'finished_at' => $now,
                    'updated_at' => $now,
                ]);
            $attempt->forceFill([
                'status' => CommunicationAttemptStatus::Processing,
                'worker_job_uuid' => $this->job?->uuid(),
                'started_at' => $now,
                'failure_stage' => null,
                'failure_code' => null,
            ])->save();
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Processing,
                'attempts_count' => $delivery->attempts_count + 1,
                'processing_at' => $now,
                'failure_stage' => null,
                'failure_code' => null,
                'lock_version' => $delivery->lock_version + 1,
            ])->save();
            $audit->record('communication_delivery.processing', $delivery, metadata: [
                'delivery_id' => $delivery->id,
                'notification_type' => $delivery->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'transition' => 'queued_to_processing',
            ]);

            return $attempt;
        }, 3);
    }

    private function finishSent(int $attemptId, AuditLogger $audit): CommunicationDelivery
    {
        return DB::transaction(function () use ($attemptId, $audit): CommunicationDelivery {
            [$delivery, $attempt] = $this->lockProcessingAttempt($attemptId);
            $now = now('UTC');
            $attempt->forceFill(['status' => CommunicationAttemptStatus::Sent, 'finished_at' => $now])->save();
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Sent,
                'sent_at' => $now,
                'processing_at' => null,
                'recipient_address' => null,
                'context' => null,
                'context_expires_at' => null,
                'failure_stage' => null,
                'failure_code' => null,
                'lock_version' => $delivery->lock_version + 1,
            ])->save();
            $audit->record('communication_delivery.sent', $delivery, metadata: [
                'delivery_id' => $delivery->id,
                'notification_type' => $delivery->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'transition' => 'processing_to_sent',
            ]);

            return $delivery;
        }, 3);
    }

    private function finishCancelled(int $attemptId, string $reasonCode, AuditLogger $audit): CommunicationDelivery
    {
        return DB::transaction(function () use ($attemptId, $reasonCode, $audit): CommunicationDelivery {
            [$delivery, $attempt] = $this->lockProcessingAttempt($attemptId);
            $now = now('UTC');
            $attempt->forceFill([
                'status' => CommunicationAttemptStatus::Cancelled,
                'failure_stage' => 'revalidation',
                'failure_code' => $reasonCode,
                'finished_at' => $now,
            ])->save();
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Cancelled,
                'cancelled_at' => $now,
                'processing_at' => null,
                'recipient_address' => null,
                'context' => null,
                'context_expires_at' => null,
                'failure_stage' => 'revalidation',
                'failure_code' => $reasonCode,
                'lock_version' => $delivery->lock_version + 1,
            ])->save();
            $audit->record('communication_delivery.cancelled', $delivery, metadata: [
                'delivery_id' => $delivery->id,
                'notification_type' => $delivery->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'transition' => 'processing_to_cancelled',
                'reason_code' => $reasonCode,
            ]);

            return $delivery;
        }, 3);
    }

    /** @return array{CommunicationDelivery, bool} */
    private function finishFailure(int $attemptId, Throwable $exception, AuditLogger $audit): array
    {
        return DB::transaction(function () use ($attemptId, $exception, $audit): array {
            [$delivery, $attempt] = $this->lockProcessingAttempt($attemptId);
            $now = now('UTC');
            $reasonCode = class_basename($exception);
            $attempt->forceFill([
                'status' => CommunicationAttemptStatus::Failed,
                'failure_stage' => 'transport',
                'failure_code' => $reasonCode,
                'finished_at' => $now,
            ])->save();
            $shouldRetry = $this->attempts() < $this->tries;
            if ($shouldRetry) {
                $nextNumber = $delivery->attempts()->max('attempt_number') + 1;
                $next = new CommunicationDeliveryAttempt;
                $next->forceFill([
                    'communication_delivery_id' => $delivery->id,
                    'attempt_number' => $nextNumber,
                    'source' => CommunicationAttemptSource::Automatic,
                    'status' => CommunicationAttemptStatus::Queued,
                    'queue' => $delivery->queue,
                    'queued_at' => $now,
                ])->save();
                $delivery->forceFill([
                    'status' => CommunicationDeliveryStatus::Queued,
                    'queued_at' => $now,
                    'processing_at' => null,
                    'failure_stage' => 'transport',
                    'failure_code' => $reasonCode,
                    'lock_version' => $delivery->lock_version + 1,
                ])->save();
            } else {
                $delivery->forceFill([
                    'status' => CommunicationDeliveryStatus::Failed,
                    'failed_at' => $now,
                    'processing_at' => null,
                    'failure_stage' => 'transport',
                    'failure_code' => $reasonCode,
                    'context_expires_at' => $now->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days')),
                    'lock_version' => $delivery->lock_version + 1,
                ])->save();
            }
            $audit->record('communication_delivery.failed', $delivery, metadata: [
                'delivery_id' => $delivery->id,
                'notification_type' => $delivery->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'transition' => $shouldRetry ? 'processing_to_queued' : 'processing_to_failed',
                'reason_code' => $reasonCode,
            ]);
            Log::warning('Falló un intento de comunicación transaccional.', [
                'delivery_id' => $delivery->id,
                'notification_type' => $delivery->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'reason_code' => $reasonCode,
            ]);

            return [$delivery, $shouldRetry];
        }, 3);
    }

    private function finishUnknown(int $attemptId, string $reasonCode, AuditLogger $audit): CommunicationDelivery
    {
        return DB::transaction(function () use ($attemptId, $reasonCode, $audit): CommunicationDelivery {
            [$delivery, $attempt] = $this->lockProcessingAttempt($attemptId);
            $now = now('UTC');
            $attempt->forceFill([
                'status' => CommunicationAttemptStatus::Unknown,
                'failure_stage' => 'state_persistence',
                'failure_code' => $reasonCode,
                'finished_at' => $now,
            ])->save();
            $delivery->forceFill([
                'status' => CommunicationDeliveryStatus::Unknown,
                'unknown_at' => $now,
                'processing_at' => null,
                'failure_stage' => 'state_persistence',
                'failure_code' => $reasonCode,
                'context_expires_at' => $now->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days')),
                'lock_version' => $delivery->lock_version + 1,
            ])->save();
            $audit->record('communication_delivery.unknown', $delivery, metadata: [
                'delivery_id' => $delivery->id,
                'notification_type' => $delivery->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'transition' => 'processing_to_unknown',
                'reason_code' => $reasonCode,
            ]);

            return $delivery;
        }, 3);
    }

    /** @return array{CommunicationDelivery, CommunicationDeliveryAttempt} */
    private function lockProcessingAttempt(int $attemptId): array
    {
        $delivery = CommunicationDelivery::query()->lockForUpdate()->findOrFail($this->communicationDeliveryId);
        $attempt = CommunicationDeliveryAttempt::query()->lockForUpdate()->findOrFail($attemptId);
        if ($delivery->status !== CommunicationDeliveryStatus::Processing
            || $attempt->communication_delivery_id !== $delivery->id
            || $attempt->status !== CommunicationAttemptStatus::Processing) {
            throw new \LogicException('The communication attempt is no longer processing.');
        }

        return [$delivery, $attempt];
    }
}
