<?php

namespace App\Console\Commands;

use App\Models\ClarificationResponseFile;
use App\Models\ResidencyDocument;
use App\Models\SubmissionFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AuditPrivateStorage extends Command
{
    protected $signature = 'flowerflow:storage-audit
        {--disk=local : Disco privado a comparar}
        {--json : Emitir un objeto JSON estable}';

    protected $description = 'Reporta archivos privados faltantes y huérfanos sin modificar storage ni base de datos.';

    public function handle(): int
    {
        $disk = (string) $this->option('disk');
        if (! config("filesystems.disks.{$disk}")) {
            $this->error("El disco [{$disk}] no está configurado.");

            return self::INVALID;
        }

        $referenced = $this->referencedPaths($disk);
        $stored = $this->storedPaths($disk);
        $missing = array_values(array_diff($referenced, $stored));
        $orphaned = array_values(array_diff($stored, $referenced));
        sort($missing);
        sort($orphaned);

        $report = [
            'disk' => $disk,
            'referenced_count' => count($referenced),
            'stored_count' => count($stored),
            'missing_count' => count($missing),
            'orphaned_count' => count($orphaned),
            'missing' => $missing,
            'orphaned' => $orphaned,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->table(
            ['Disco', 'Referencias', 'Almacenados', 'Faltantes', 'Huérfanos'],
            [[$disk, count($referenced), count($stored), count($missing), count($orphaned)]]
        );
        $this->listPaths('Faltantes', $missing);
        $this->listPaths('Huérfanos', $orphaned);

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function referencedPaths(string $disk): array
    {
        $paths = collect();

        if (Schema::hasTable('submission_files')) {
            $paths->push(...SubmissionFile::query()->where('disk', $disk)->pluck('path'));
        }
        if (Schema::hasTable('residency_documents')) {
            $paths->push(...ResidencyDocument::query()->where('disk', $disk)->pluck('path'));
        }
        if (Schema::hasTable('clarification_response_files')) {
            $paths->push(...ClarificationResponseFile::query()->where('disk', $disk)->pluck('path'));
        }

        return $paths->filter()->unique()->sort()->values()->all();
    }

    /** @return list<string> */
    private function storedPaths(string $disk): array
    {
        $filesystem = Storage::disk($disk);
        $paths = $disk === 'local'
            ? $filesystem->allFiles('submissions')
            : $filesystem->allFiles();

        sort($paths);

        return array_values(array_unique($paths));
    }

    /** @param list<string> $paths */
    private function listPaths(string $title, array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $this->newLine();
        $this->warn($title.':');
        foreach ($paths as $path) {
            $this->line(' - '.$path);
        }
    }
}
