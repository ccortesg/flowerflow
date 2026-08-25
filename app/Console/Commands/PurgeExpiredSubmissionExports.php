<?php

namespace App\Console\Commands;

use App\Enums\EvaluationExportStatus;
use App\Enums\SubmissionExportStatus;
use App\Models\EvaluationExport;
use App\Models\SubmissionExport;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PurgeExpiredSubmissionExports extends Command
{
    protected $signature = 'flowerflow:exports-purge {--dry-run : Sólo reporta las exportaciones vencidas}';

    protected $description = 'Elimina archivos XLSX privados vencidos y conserva su registro de auditoría';

    public function handle(AuditLogger $auditLogger): int
    {
        $expired = SubmissionExport::query()
            ->where('status', SubmissionExportStatus::Completed->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now('UTC'))
            ->orderBy('id')
            ->get();
        $expiredEvaluations = EvaluationExport::query()
            ->where('status', EvaluationExportStatus::Completed->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now('UTC'))
            ->orderBy('id')
            ->get();

        if ($this->option('dry-run')) {
            $this->info("Exportaciones vencidas: {$expired->count()}. No se eliminó ningún archivo.");
            $this->info("Exportaciones de evaluaciones vencidas: {$expiredEvaluations->count()}. No se eliminó ningún archivo.");

            return self::SUCCESS;
        }

        $purged = 0;
        foreach ($expired as $export) {
            $expectedPrefix = "submission-exports/{$export->public_id}/";
            if ($export->path && ! str_starts_with($export->path, $expectedPrefix)) {
                $this->error("Ruta fuera del alcance permitido para {$export->public_id}.");

                return self::FAILURE;
            }

            if ($export->path) {
                Storage::disk($export->disk)->delete($export->path);
            }

            $export->forceFill([
                'status' => SubmissionExportStatus::Expired,
                'path' => null,
            ])->save();

            $auditLogger->record('submission_export.expired', $export, $export->requestedBy, [
                'expired_at' => $export->expires_at?->utc()->toIso8601String(),
            ]);
            $purged++;
        }

        $evaluationPurged = 0;
        foreach ($expiredEvaluations as $export) {
            $expectedPrefix = "evaluation-exports/{$export->public_id}/";
            if ($export->path && ! str_starts_with($export->path, $expectedPrefix)) {
                $this->error("Ruta de evaluación fuera del alcance permitido para {$export->public_id}.");

                return self::FAILURE;
            }
            if ($export->path) {
                Storage::disk($export->disk)->delete($export->path);
            }
            $export->forceFill([
                'status' => EvaluationExportStatus::Expired,
                'path' => null,
            ])->save();
            $auditLogger->record('evaluation_export.expired', $export, $export->requestedBy, [
                'scope_version' => $export->scope_version,
                'status' => EvaluationExportStatus::Expired->value,
                'expired_at' => $export->expires_at?->utc()->toIso8601String(),
            ]);
            $evaluationPurged++;
        }

        $this->info("Exportaciones depuradas: {$purged}.");
        $this->info("Exportaciones de evaluaciones depuradas: {$evaluationPurged}.");

        return self::SUCCESS;
    }
}
