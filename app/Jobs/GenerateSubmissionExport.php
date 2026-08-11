<?php

namespace App\Jobs;

use App\Enums\SubmissionExportStatus;
use App\Models\SubmissionExport;
use App\Services\AuditLogger;
use App\Services\SubmissionWorkbookWriter;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateSubmissionExport implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public int $uniqueFor = 600;

    public function __construct(public int $submissionExportId)
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
        return (string) $this->submissionExportId;
    }

    public function handle(SubmissionWorkbookWriter $workbookWriter, AuditLogger $auditLogger): void
    {
        $export = SubmissionExport::query()->findOrFail($this->submissionExportId);
        if ($export->status === SubmissionExportStatus::Completed || $export->status === SubmissionExportStatus::Expired) {
            return;
        }

        $export->forceFill([
            'status' => SubmissionExportStatus::Processing,
            'failed_at' => null,
            'failure_code' => null,
        ])->save();

        $temporaryPath = tempnam(sys_get_temp_dir(), 'flowerflow-export-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create the temporary export file.');
        }

        try {
            $counts = $workbookWriter->write($temporaryPath);
            $fileName = 'flower-flow-propuestas-'.now(config('flowerflow.timezone'))->format('Ymd-His').'.xlsx';
            $path = "submission-exports/{$export->public_id}/{$fileName}";
            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Unable to open the generated export file.');
            }

            try {
                $stored = Storage::disk($export->disk)->put($path, $stream);
            } finally {
                fclose($stream);
            }
            if (! $stored) {
                Storage::disk($export->disk)->delete($path);

                throw new RuntimeException('Unable to persist the generated export file.');
            }

            try {
                DB::transaction(function () use ($export, $path, $fileName, $counts, $auditLogger): void {
                    $export->forceFill([
                        'status' => SubmissionExportStatus::Completed,
                        'path' => $path,
                        'file_name' => $fileName,
                        ...$counts,
                        'completed_at' => now('UTC'),
                        'expires_at' => now('UTC')->addHours((int) config('flowerflow.exports.retention_hours')),
                    ])->save();

                    $auditLogger->record('submission_export.completed', $export, $export->requestedBy, [
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
        $export = SubmissionExport::query()->find($this->submissionExportId);
        if (! $export) {
            return;
        }

        $export->forceFill([
            'status' => SubmissionExportStatus::Failed,
            'failed_at' => now('UTC'),
            'failure_code' => class_basename($exception),
        ])->save();

        app(AuditLogger::class)->record('submission_export.failed', $export, $export->requestedBy, [
            'failure_code' => class_basename($exception),
        ]);
    }
}
