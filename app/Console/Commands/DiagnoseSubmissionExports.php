<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnoseSubmissionExports extends Command
{
    protected $signature = 'flowerflow:exports-diagnose {--json : Emitir JSON para automatización}';

    protected $description = 'Diagnostica en modo de sólo lectura la cola y el almacenamiento privado de exportaciones';

    public function handle(): int
    {
        $connection = (string) config('flowerflow.exports.queue_connection');
        $queue = (string) config('flowerflow.exports.queue');
        $disk = (string) config('flowerflow.exports.disk');
        $diskRoot = config("filesystems.disks.{$disk}.root");
        $staleBefore = now()->subMinutes((int) config('flowerflow.exports.stalled_after_minutes'));

        $result = [
            'queue_connection' => $connection,
            'queue_name' => $queue,
            'export_disk' => $disk,
            'submission_exports_table' => Schema::hasTable('submission_exports'),
            'evaluation_exports_table' => Schema::hasTable('evaluation_exports'),
            'jobs_table' => Schema::hasTable('jobs'),
            'failed_jobs_table' => Schema::hasTable('failed_jobs'),
            'queued_jobs' => null,
            'oldest_job_minutes' => null,
            'failed_jobs' => null,
            'queued_exports' => null,
            'stale_exports' => null,
            'failed_exports' => null,
            'queued_evaluation_exports' => null,
            'stale_evaluation_exports' => null,
            'failed_evaluation_exports' => null,
            'disk_directory_exists' => is_string($diskRoot) && is_dir($diskRoot),
            'disk_directory_writable' => is_string($diskRoot) && is_dir($diskRoot) && is_writable($diskRoot),
            'disk_free_megabytes' => is_string($diskRoot) && is_dir($diskRoot)
                ? round(((float) disk_free_space($diskRoot)) / 1024 / 1024, 2)
                : null,
            'diagnostic_error_code' => null,
        ];

        try {
            if ($result['jobs_table']) {
                $jobs = DB::table('jobs')->where('queue', $queue);
                $oldestCreatedAt = (clone $jobs)->min('created_at');
                $result['queued_jobs'] = (clone $jobs)->count();
                $result['oldest_job_minutes'] = $oldestCreatedAt
                    ? max(0, (int) floor((now()->timestamp - (int) $oldestCreatedAt) / 60))
                    : null;
            }

            if ($result['failed_jobs_table']) {
                $result['failed_jobs'] = DB::table('failed_jobs')->where('queue', $queue)->count();
            }

            if ($result['submission_exports_table']) {
                $result['queued_exports'] = DB::table('submission_exports')->where('status', 'queued')->count();
                $result['stale_exports'] = DB::table('submission_exports')
                    ->where('status', 'queued')
                    ->where('created_at', '<=', $staleBefore)
                    ->count();
                $result['failed_exports'] = DB::table('submission_exports')->where('status', 'failed')->count();
            }
            if ($result['evaluation_exports_table']) {
                $result['queued_evaluation_exports'] = DB::table('evaluation_exports')->where('status', 'queued')->count();
                $result['stale_evaluation_exports'] = DB::table('evaluation_exports')
                    ->where('status', 'queued')
                    ->where('created_at', '<=', $staleBefore)
                    ->count();
                $result['failed_evaluation_exports'] = DB::table('evaluation_exports')->where('status', 'failed')->count();
            }
        } catch (Throwable $exception) {
            $result['diagnostic_error_code'] = class_basename($exception);
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['Verificación', 'Valor'],
            collect($result)->map(fn ($value, $key) => [
                $key,
                match (true) {
                    $value === true => 'sí',
                    $value === false => 'no',
                    $value === null => 'no disponible',
                    default => (string) $value,
                },
            ])->values()->all(),
        );
        $this->info('Diagnóstico completado sin modificar jobs, exportaciones ni archivos.');

        return self::SUCCESS;
    }
}
