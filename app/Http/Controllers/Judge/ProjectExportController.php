<?php

namespace App\Http\Controllers\Judge;

use App\Exceptions\BlindReviewPackageRejected;
use App\Http\Controllers\Controller;
use App\Models\BlindReviewPackage;
use App\Models\JudgeAssignment;
use App\Services\AuditLogger;
use App\Services\BlindReviewProjectResolver;
use App\Services\JudgeProjectPdfExporter;
use App\Services\JudgeProjectWorkbookWriter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProjectExportController extends Controller
{
    public function pdf(
        JudgeAssignment $judgeAssignment,
        BlindReviewProjectResolver $resolver,
        JudgeProjectPdfExporter $exporter,
        AuditLogger $audit,
        Filesystem $files,
    ): BinaryFileResponse {
        return $this->export($judgeAssignment, 'pdf', $resolver, $audit, $files,
            fn (BlindReviewPackage $package, string $path) => $exporter->write($judgeAssignment, $package, $path));
    }

    public function xlsx(
        JudgeAssignment $judgeAssignment,
        BlindReviewProjectResolver $resolver,
        JudgeProjectWorkbookWriter $writer,
        AuditLogger $audit,
        Filesystem $files,
    ): BinaryFileResponse {
        return $this->export($judgeAssignment, 'xlsx', $resolver, $audit, $files,
            fn (BlindReviewPackage $package, string $path) => $writer->write($judgeAssignment, $package, $path));
    }

    /** @param callable(BlindReviewPackage,string):void $generate */
    private function export(
        JudgeAssignment $assignment,
        string $format,
        BlindReviewProjectResolver $resolver,
        AuditLogger $audit,
        Filesystem $files,
        callable $generate,
    ): BinaryFileResponse {
        $path = null;
        $package = null;

        try {
            $package = $resolver->resolve($assignment, request()->user());
            $directory = storage_path('app/private/judge-project-exports');
            $files->ensureDirectoryExists($directory, 0700, true);
            $path = tempnam($directory, 'project-');
            if ($path === false) {
                throw new RuntimeException('Unable to allocate a private export file.');
            }
            $generate($package, $path);
        } catch (AuthorizationException $exception) {
            $this->auditRejection($audit, $assignment, $format, 'authorization_denied');
            throw $exception;
        } catch (BlindReviewPackageRejected $exception) {
            $this->auditRejection($audit, $assignment, $format, $exception->reasonCode);
            abort(in_array($exception->reasonCode, [
                'actor_not_authorized',
                'assignment_owner_not_operational',
                'assignment_not_active',
            ], true) ? 403 : 409, $exception->getMessage());
        } catch (Throwable $exception) {
            if ($path && $files->exists($path)) {
                $files->delete($path);
            }
            $this->auditRejection($audit, $package ?? $assignment, $format, 'export_generation_failed', $assignment);
            report($exception);
            abort(500, 'No fue posible generar la exportación del proyecto. Intenta nuevamente.');
        }

        $audit->record('blind_review_package.project_exported', $package, request()->user(), [
            'assignment_id' => $assignment->id,
            'format' => $format,
            'external_link_count' => count(data_get($package->payload, 'external_links', [])),
            'attachment_count' => $package->files->count(),
        ]);

        return response()->download(
            $path,
            'flower-flow-proyecto-'.$assignment->public_id.'.'.$format,
            [
                'Content-Type' => $format === 'pdf'
                    ? 'application/pdf'
                    : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        )->deleteFileAfterSend(true);
    }

    private function auditRejection(
        AuditLogger $audit,
        JudgeAssignment|BlindReviewPackage $subject,
        string $format,
        string $reasonCode,
        ?JudgeAssignment $assignment = null,
    ): void {
        $assignment ??= $subject instanceof JudgeAssignment ? $subject : null;
        $audit->record('blind_review_package.project_export_rejected', $subject, request()->user(), [
            ...($assignment ? ['assignment_id' => $assignment->id] : []),
            'format' => $format,
            'reason_code' => $reasonCode,
        ]);
    }
}
