<?php

namespace App\Http\Controllers\Judge;

use App\Exceptions\BlindReviewPackageRejected;
use App\Http\Controllers\Controller;
use App\Models\BlindReviewPackageFile;
use App\Models\JudgeAssignment;
use App\Services\AuditLogger;
use App\Services\BlindReviewFileIntegrityVerifier;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BlindReviewPackageFileController extends Controller
{
    public function __invoke(
        JudgeAssignment $judgeAssignment,
        BlindReviewPackageFile $blindReviewPackageFile,
        BlindReviewFileIntegrityVerifier $integrity,
        AuditLogger $audit,
    ): BinaryFileResponse {
        Gate::authorize('download', [$blindReviewPackageFile, $judgeAssignment]);

        try {
            $path = $integrity->verifyPackageFile($blindReviewPackageFile);
        } catch (BlindReviewPackageRejected $exception) {
            $audit->record('blind_review_package.file_download_rejected', $blindReviewPackageFile, request()->user(), [
                'assignment_id' => $judgeAssignment->id,
                'package_id' => $blindReviewPackageFile->blind_review_package_id,
                'reason_code' => $exception->reasonCode,
            ]);
            abort(409, 'El anexo no está disponible porque su integridad no pudo verificarse.');
        }

        $audit->record('blind_review_package.file_downloaded', $blindReviewPackageFile, request()->user(), [
            'assignment_id' => $judgeAssignment->id,
            'package_id' => $blindReviewPackageFile->blind_review_package_id,
            'file_class' => $blindReviewPackageFile->file_class->value,
            'expected_sha256' => $blindReviewPackageFile->expected_sha256,
        ]);

        return response()->download($path, $blindReviewPackageFile->neutral_label, [
            'Content-Type' => $blindReviewPackageFile->expected_mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
