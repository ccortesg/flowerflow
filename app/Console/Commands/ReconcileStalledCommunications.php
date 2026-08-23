<?php

namespace App\Console\Commands;

use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use App\Services\AuditLogger;
use App\Services\CommunicationDeliveryStateSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStalledCommunications extends Command
{
    protected $signature = 'flowerflow:communications-reconcile';

    protected $description = 'Move stale processing communications to unknown without retrying them';

    public function handle(AuditLogger $audit, CommunicationDeliveryStateSynchronizer $synchronizer): int
    {
        if (! config('flowerflow.flags.communication_ledger')) {
            $this->components->info('Communication ledger disabled; no state was changed.');

            return self::SUCCESS;
        }

        $stalledBefore = now('UTC')->subMinutes((int) config('flowerflow.communication_ledger.stalled_after_minutes'));
        $ids = CommunicationDelivery::query()
            ->where('status', CommunicationDeliveryStatus::Processing->value)
            ->where('processing_at', '<=', $stalledBefore)
            ->pluck('id');
        $changed = 0;
        foreach ($ids as $id) {
            $delivery = DB::transaction(function () use ($id, $stalledBefore, $audit): ?CommunicationDelivery {
                $locked = CommunicationDelivery::query()->lockForUpdate()->find($id);
                if (! $locked || $locked->status !== CommunicationDeliveryStatus::Processing || $locked->processing_at?->isAfter($stalledBefore)) {
                    return null;
                }
                $now = now('UTC');
                $attempt = $locked->attempts()->where('status', CommunicationAttemptStatus::Processing->value)->latest('attempt_number')->lockForUpdate()->first();
                if ($attempt) {
                    $attempt->forceFill([
                        'status' => CommunicationAttemptStatus::Unknown,
                        'failure_stage' => 'reconciliation',
                        'failure_code' => 'processing_lease_expired',
                        'finished_at' => $now,
                    ])->save();
                }
                $locked->forceFill([
                    'status' => CommunicationDeliveryStatus::Unknown,
                    'unknown_at' => $now,
                    'processing_at' => null,
                    'failure_stage' => 'reconciliation',
                    'failure_code' => 'processing_lease_expired',
                    'context_expires_at' => $now->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days')),
                    'lock_version' => $locked->lock_version + 1,
                ])->save();
                $audit->record('communication_delivery.unknown', $locked, metadata: [
                    'delivery_id' => $locked->id,
                    'notification_type' => $locked->notification_type->value,
                    'attempt_number' => $attempt?->attempt_number,
                    'transition' => 'processing_to_unknown',
                    'reason_code' => 'processing_lease_expired',
                ]);

                return $locked;
            }, 3);
            if ($delivery) {
                $synchronizer->sync($delivery);
                $changed++;
            }
        }

        $this->components->info("Reconciled communications: {$changed}");

        return self::SUCCESS;
    }
}
