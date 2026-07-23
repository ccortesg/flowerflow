<?php

namespace App\Console\Commands;

use App\Models\ResidencyDocumentRequest;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ReportResidencyRetention extends Command
{
    protected $signature = 'flowerflow:residency-retention-report {--as-of= : Fecha UTC de corte para el reporte}';

    protected $description = 'Reporta candidatos de retención de residencia sin eliminar archivos';

    public function handle(): int
    {
        $asOf = $this->option('as-of') ? CarbonImmutable::parse($this->option('as-of'), 'UTC') : now('UTC');
        $requests = ResidencyDocumentRequest::query()
            ->with(['review.submission:id,public_id,folio', 'subjectUser:id,public_id', 'subjectTeamMember:id'])
            ->whereNotNull('retention_due_at')
            ->where('retention_due_at', '<=', $asOf)
            ->orderBy('retention_due_at')
            ->get();

        $this->table(
            ['Solicitud', 'Folio', 'Sujeto', 'Fecha candidata UTC', 'Estado'],
            $requests->map(fn ($request) => [
                $request->public_id,
                $request->review->submission->folio,
                $request->subject_user_id ? 'representante' : 'integrante:'.$request->subject_team_member_id,
                $request->retention_due_at->utc()->format('Y-m-d H:i:s'),
                $request->status->value,
            ])->all()
        );
        $this->warn('Dry-run: no se eliminó ningún documento. La determinación de ganadores sigue siendo un requisito previo.');

        return self::SUCCESS;
    }
}
