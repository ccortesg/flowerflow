<?php

namespace App\Console\Commands;

use App\Exceptions\EvaluationCloseDigestRejected;
use App\Services\EvaluationCloseDigest;
use Illuminate\Console\Command;

class QueueEvaluationCloseDigests extends Command
{
    protected $signature = 'flowerflow:evaluations-queue-close-digests {--execute : Persist and enqueue eligible digests; without this option the command is read-only}';

    protected $description = 'Preview or queue one redacted evaluation-close digest per judge';

    public function handle(EvaluationCloseDigest $digests): int
    {
        try {
            $result = $digests->queue((bool) $this->option('execute'));
        } catch (EvaluationCloseDigestRejected $exception) {
            $result = [
                'status' => 'rejected',
                'reason_code' => $exception->reasonCode,
                'judges_considered' => 0,
                'deliveries_requested' => 0,
                'deliveries_skipped' => 0,
            ];
        }

        $this->table(['Metric', 'Value'], collect($result)->map(fn ($value, $key) => [$key, $value])->values()->all());

        return $result['status'] === 'rejected' ? self::FAILURE : self::SUCCESS;
    }
}
