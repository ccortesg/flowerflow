<?php

namespace App\Http\Controllers\Panel;

use App\Enums\SubmissionExportStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSubmissionExport;
use App\Models\Submission;
use App\Models\SubmissionExport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SubmissionExportController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', SubmissionExport::class);

        return view('panel.submissions.exports.create', [
            'proposalCount' => Submission::query()->whereIn('status', ['draft', 'submitted'])->count(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorize('create', SubmissionExport::class);
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        if ($confirmedAt < time() - (int) config('auth.password_timeout', 10800)) {
            return redirect()->route('panel.submissions.exports.create')
                ->with('warning', 'Confirma nuevamente tu contraseña antes de generar la exportación.');
        }

        $export = DB::transaction(function () use ($request, $auditLogger): SubmissionExport {
            $export = $request->user()->submissionExports()->create([
                'status' => SubmissionExportStatus::Queued,
                'filters' => ['statuses' => ['draft', 'submitted']],
                'disk' => config('flowerflow.exports.disk'),
            ]);

            $auditLogger->record('submission_export.requested', $export, $request->user(), [
                'statuses' => ['draft', 'submitted'],
            ]);

            return $export;
        }, 3);

        try {
            GenerateSubmissionExport::dispatch($export->id);
        } catch (Throwable $exception) {
            $export->forceFill([
                'status' => SubmissionExportStatus::Failed,
                'failed_at' => now('UTC'),
                'failure_code' => class_basename($exception),
            ])->save();
            $auditLogger->record('submission_export.failed', $export, $request->user(), [
                'failure_code' => class_basename($exception),
            ]);
            report($exception);

            return redirect()->route('panel.submissions.index')
                ->with('warning', 'No fue posible programar la exportación. Inténtalo nuevamente.');
        }

        return redirect()->route('panel.submissions.index')
            ->with('status', 'La exportación se está generando. Actualiza esta pantalla para consultar su estado.');
    }

    public function download(Request $request, SubmissionExport $submissionExport, AuditLogger $auditLogger): StreamedResponse
    {
        $this->authorize('download', $submissionExport);
        abort_if($submissionExport->expires_at?->isPast(), 410, 'La exportación expiró. Genera una nueva.');
        abort_unless($submissionExport->isAvailable(), 404);
        abort_unless(Storage::disk($submissionExport->disk)->exists($submissionExport->path), 404);

        $auditLogger->record('submission_export.downloaded', $submissionExport, $request->user(), [
            'proposal_count' => $submissionExport->proposal_count,
        ]);

        return Storage::disk($submissionExport->disk)->download(
            $submissionExport->path,
            $submissionExport->file_name,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
