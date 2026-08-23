<?php

namespace App\Console\Commands;

use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeCommunicationContext extends Command
{
    protected $signature = 'flowerflow:communications-purge-context';

    protected $description = 'Purge encrypted recipient and context data after its operational retention window';

    public function handle(): int
    {
        if (! config('flowerflow.flags.communication_ledger')) {
            $this->components->info('Communication ledger disabled; no context was changed.');

            return self::SUCCESS;
        }

        $purged = DB::transaction(function (): int {
            $terminal = CommunicationDelivery::query()
                ->whereIn('status', [CommunicationDeliveryStatus::Sent->value, CommunicationDeliveryStatus::Cancelled->value])
                ->where(fn ($query) => $query->whereNotNull('recipient_address')->orWhereNotNull('context'))
                ->update(['recipient_address' => null, 'context' => null, 'context_expires_at' => null, 'updated_at' => now('UTC')]);
            $expired = CommunicationDelivery::query()
                ->whereIn('status', [CommunicationDeliveryStatus::Failed->value, CommunicationDeliveryStatus::Unknown->value])
                ->whereNotNull('context_expires_at')
                ->where('context_expires_at', '<=', now('UTC'))
                ->where(fn ($query) => $query->whereNotNull('recipient_address')->orWhereNotNull('context'))
                ->update(['recipient_address' => null, 'context' => null, 'updated_at' => now('UTC')]);

            return $terminal + $expired;
        }, 3);

        $this->components->info("Purged communication contexts: {$purged}");

        return self::SUCCESS;
    }
}
