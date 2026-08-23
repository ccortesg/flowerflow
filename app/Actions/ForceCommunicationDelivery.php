<?php

namespace App\Actions;

use App\Enums\CommunicationAttemptSource;
use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Jobs\DeliverCommunication;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationDeliveryAttempt;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CommunicationDeliveryStateSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class ForceCommunicationDelivery
{
    public function __construct(
        private AuditLogger $audit,
        private CommunicationDeliveryStateSynchronizer $synchronizer,
    ) {}

    public function execute(
        CommunicationDelivery $delivery,
        User $actor,
        int $lockVersion,
        string $reason,
        bool $duplicateRiskAcknowledged,
    ): CommunicationDelivery {
        $this->assertActor($actor);
        $queue = (string) config('flowerflow.communication_ledger.force_queue');

        $result = DB::transaction(function () use (
            $delivery,
            $actor,
            $lockVersion,
            $reason,
            $duplicateRiskAcknowledged,
            $queue,
        ): CommunicationDelivery {
            $locked = CommunicationDelivery::query()->lockForUpdate()->findOrFail($delivery->id);
            if ($locked->lock_version !== $lockVersion) {
                throw new ConflictHttpException('La bitácora cambió mientras la revisabas. Actualiza la página antes de continuar.');
            }
            if (! $locked->status->canBeForced()) {
                throw new ConflictHttpException('Esta comunicación ya no admite procesamiento o reintento.');
            }
            if ($locked->recipient_address === null || $locked->context === null) {
                throw ValidationException::withMessages([
                    'delivery' => 'El contexto seguro ya no está disponible; la comunicación no puede reenviarse.',
                ]);
            }
            if ($locked->status === CommunicationDeliveryStatus::Unknown && ! $duplicateRiskAcknowledged) {
                throw ValidationException::withMessages([
                    'duplicate_risk_acknowledged' => 'Debes reconocer el posible envío duplicado.',
                ]);
            }

            $now = now('UTC');
            $locked->attempts()->where('status', CommunicationAttemptStatus::Queued->value)->update([
                'status' => CommunicationAttemptStatus::Cancelled->value,
                'failure_stage' => 'admin_force',
                'failure_code' => 'superseded_by_admin',
                'finished_at' => $now,
                'updated_at' => $now,
            ]);
            $attempt = new CommunicationDeliveryAttempt;
            $attempt->forceFill([
                'communication_delivery_id' => $locked->id,
                'attempt_number' => ((int) $locked->attempts()->max('attempt_number')) + 1,
                'source' => CommunicationAttemptSource::AdminForced,
                'requested_by_user_id' => $actor->id,
                'reason' => $reason,
                'status' => CommunicationAttemptStatus::Queued,
                'queue' => $queue,
                'duplicate_risk_acknowledged' => $duplicateRiskAcknowledged,
                'queued_at' => $now,
            ])->save();
            $previous = $locked->status;
            $locked->forceFill([
                'status' => CommunicationDeliveryStatus::Queued,
                'queue' => $queue,
                'queued_at' => $now,
                'processing_at' => null,
                'failure_stage' => null,
                'failure_code' => null,
                'context_expires_at' => null,
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $this->audit->record('communication_delivery.forced', $locked, $actor, [
                'delivery_id' => $locked->id,
                'notification_type' => $locked->notification_type->value,
                'attempt_number' => $attempt->attempt_number,
                'transition' => $previous->value.'_to_queued',
                'duplicate_risk_acknowledged' => $duplicateRiskAcknowledged,
            ]);

            return $locked;
        }, 3);

        $this->synchronizer->sync($result);
        try {
            $job = new DeliverCommunication($result->id);
            $job->onQueue($queue);
            dispatch($job);
        } catch (Throwable $exception) {
            $this->markEnqueueFailure($result, $exception);
            throw ValidationException::withMessages([
                'delivery' => 'No fue posible programar la comunicación. El fallo quedó registrado para diagnóstico.',
            ]);
        }

        return $result->fresh(['attempts']);
    }

    private function assertActor(User $actor): void
    {
        if (! config('flowerflow.flags.communication_ledger')
            || ! $actor->hasExactRoles(['admin'])
            || ! $actor->can('manage communication deliveries')) {
            throw ValidationException::withMessages(['role' => 'La cuenta no puede gestionar comunicaciones.']);
        }
    }

    private function markEnqueueFailure(CommunicationDelivery $delivery, Throwable $exception): void
    {
        $updated = DB::transaction(function () use ($delivery, $exception): CommunicationDelivery {
            $locked = CommunicationDelivery::query()->lockForUpdate()->findOrFail($delivery->id);
            $now = now('UTC');
            $code = class_basename($exception);
            $attempt = $locked->attempts()->where('status', CommunicationAttemptStatus::Queued->value)->latest('attempt_number')->first();
            if ($attempt) {
                $attempt->forceFill([
                    'status' => CommunicationAttemptStatus::Failed,
                    'failure_stage' => 'enqueue',
                    'failure_code' => $code,
                    'finished_at' => $now,
                ])->save();
            }
            $locked->forceFill([
                'status' => CommunicationDeliveryStatus::Failed,
                'failure_stage' => 'enqueue',
                'failure_code' => $code,
                'failed_at' => $now,
                'context_expires_at' => $now->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days')),
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            return $locked;
        }, 3);
        $this->synchronizer->sync($updated);
    }
}
