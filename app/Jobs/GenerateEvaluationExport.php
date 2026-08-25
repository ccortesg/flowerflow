<?php

namespace App\Jobs;

use App\Enums\EvaluationExportStatus;
use App\Models\EvaluationExport;
use App\Services\AuditLogger;
use App\Services\EvaluationWorkbookWriter;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateEvaluationExport implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public int $uniqueFor = 600;

    public function __construct(public int $evaluationExportId)
    {
        $this->tries = (int) config('flowerflow.exports.tries');
        $this->timeout = (int) config('flowerflow.exports.timeout');
        $this->onConnection(config('flowerflow.exports.queue_connection'));
        $this->onQueue(config('flowerflow.exports.queue'));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return config('flowerflow.exports.backoff');
    }

    public function uniqueId(): string
    {
        return (string) $this->evaluationExportId;
    }

    public function handle(EvaluationWorkbookWriter $writer, AuditLogger $audit): void
    {
        $export = EvaluationExport::query()->findOrFail($this->evaluationExportId);
        if (in_array($export->status, [EvaluationExportStatus::Completed, EvaluationExportStatus::Expired], true)) {
            return;
        }
        if (! config('flowerflow.flags.evaluation_export')) {
            $this->markFailed($export, 'feature_disabled', $audit);

            return;
        }
        if ($export->scope_version !== 'all_revisions_v1') {
            throw new RuntimeException('Unknown evaluation export scope.');
        }

        $export->forceFill([
            'status' => EvaluationExportStatus::Processing,
            'failed_at' => null,
            'failure_code' => null,
        ])->save();

        $temporaryPath = tempnam(sys_get_temp_dir(), 'flowerflow-evaluation-export-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create the temporary evaluation export file.');
        }

        try {
            $counts = $writer->write($temporaryPath);
            $fileName = 'flower-flow-evaluaciones-'.now(config('flowerflow.timezone'))->format('Ymd-His').'.xlsx';
            $path = "evaluation-exports/{$export->public_id}/{$fileName}";
            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Unable to open the generated evaluation export file.');
            }

            try {
                $stored = Storage::disk($export->disk)->put($path, $stream);
            } finally {
                fclose($stream);
            }
            if (! $stored) {
                Storage::disk($export->disk)->delete($path);
                throw new RuntimeException('Unable to persist the generated evaluation export file.');
            }

            try {
                DB::transaction(function () use ($export, $path, $fileName, $counts, $audit): void {
                    $export->forceFill([
                        'status' => EvaluationExportStatus::Completed,
                        'path' => $path,
                        'file_name' => $fileName,
                        ...$counts,
                        'completed_at' => now('UTC'),
                        'expires_at' => now('UTC')->addHours((int) config('flowerflow.exports.retention_hours')),
                    ])->save();

                    $audit->record('evaluation_export.completed', $export, $export->requestedBy, [
                        'scope_version' => $export->scope_version,
                        ...$counts,
                        'expires_at' => $export->expires_at?->utc()->toIso8601String(),
                    ]);
                }, 3);
            } catch (Throwable $exception) {
                Storage::disk($export->disk)->delete($path);
                throw $exception;
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        $export = EvaluationExport::query()->find($this->evaluationExportId);
        if (! $export) {
            return;
        }

        $this->markFailed($export, class_basename($exception), app(AuditLogger::class));
    }

    private function markFailed(EvaluationExport $export, string $failureCode, AuditLogger $audit): void
    {
        if ($export->path && str_starts_with($export->path, "evaluation-exports/{$export->public_id}/")) {
            Storage::disk($export->disk)->delete($export->path);
        }
        $export->forceFill([
            'status' => EvaluationExportStatus::Failed,
            'path' => null,
            'failed_at' => now('UTC'),
            'failure_code' => $failureCode,
        ])->save();

        $audit->record('evaluation_export.failed', $export, $export->requestedBy, [
            'scope_version' => $export->scope_version,
            'status' => EvaluationExportStatus::Failed->value,
            'failure_code' => $failureCode,
        ]);
    }
}
