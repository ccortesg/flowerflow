<?php

namespace App\Services;

use App\Models\BlindReviewPackage;
use App\Models\JudgeAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Filesystem\Filesystem;

final class JudgeProjectPdfExporter
{
    public function __construct(private Filesystem $files) {}

    public function write(JudgeAssignment $assignment, BlindReviewPackage $package, string $path): void
    {
        $cacheDirectory = storage_path('framework/cache/dompdf');
        $this->files->ensureDirectoryExists($cacheDirectory, 0700, true);

        Pdf::loadView('judge.exports.project-pdf', [
            'assignment' => $assignment,
            'package' => $package,
            'payload' => $package->payload,
            'flowerFlowLogo' => $this->dataUri(public_path('assets/flowerflow/logo_flowerflow_transparente.png')),
            'floreceLogo' => $this->dataUri(public_path('assets/flowerflow/logo_florecehermosillo_transparente.png')),
        ])->setPaper('a4', 'portrait')->save($path);
    }

    private function dataUri(string $path): string
    {
        return 'data:image/png;base64,'.base64_encode($this->files->get($path));
    }
}
