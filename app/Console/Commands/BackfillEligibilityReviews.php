<?php

namespace App\Console\Commands;

use App\Actions\EnsureEligibilityReview;
use App\Models\Submission;
use Illuminate\Console\Command;

class BackfillEligibilityReviews extends Command
{
    protected $signature = 'flowerflow:admissibility-backfill {--dry-run : Sólo muestra cuántos expedientes faltan}';

    protected $description = 'Crea de forma idempotente expedientes faltantes para propuestas enviadas';

    public function handle(EnsureEligibilityReview $ensure): int
    {
        $query = Submission::query()
            ->where('status', 'submitted')
            ->whereDoesntHave('eligibilityReview')
            ->whereHas('versions')
            ->with(['versions' => fn ($query) => $query->orderByDesc('version')]);

        $missing = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("Expedientes faltantes: $missing. No se modificaron datos.");

            return self::SUCCESS;
        }

        $created = 0;
        $query->chunkById(100, function ($submissions) use ($ensure, &$created): void {
            foreach ($submissions as $submission) {
                $review = $ensure->execute($submission, $submission->versions->first());
                $created += $review->wasRecentlyCreated ? 1 : 0;
            }
        });

        $this->info("Expedientes creados: $created. Expedientes faltantes detectados: $missing.");

        return self::SUCCESS;
    }
}
