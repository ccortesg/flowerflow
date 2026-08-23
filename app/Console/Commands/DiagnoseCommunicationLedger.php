<?php

namespace App\Console\Commands;

use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use Illuminate\Console\Command;

class DiagnoseCommunicationLedger extends Command
{
    protected $signature = 'flowerflow:communications-diagnose';

    protected $description = 'Read-only communication ledger health summary without recipient data';

    public function handle(): int
    {
        $stalledBefore = now('UTC')->subMinutes((int) config('flowerflow.communication_ledger.stalled_after_minutes'));
        $counts = CommunicationDelivery::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $stalledQueued = CommunicationDelivery::query()
            ->where('status', CommunicationDeliveryStatus::Queued->value)
            ->where('queued_at', '<=', $stalledBefore)
            ->count();
        $stalledProcessing = CommunicationDelivery::query()
            ->where('status', CommunicationDeliveryStatus::Processing->value)
            ->where('processing_at', '<=', $stalledBefore)
            ->count();

        $this->table(['Metric', 'Count'], [
            ...collect(CommunicationDeliveryStatus::cases())->map(fn ($status) => [$status->value, (int) ($counts[$status->value] ?? 0)])->all(),
            ['stalled_queued', $stalledQueued],
            ['stalled_processing', $stalledProcessing],
        ]);

        return self::SUCCESS;
    }
}
