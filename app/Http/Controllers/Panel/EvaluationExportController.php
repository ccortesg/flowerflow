<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EvaluationExportStatus;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateEvaluationExport;
use App\Models\Evaluation;
use App\Models\EvaluationExport;
use App\Models\EvaluationReopening;
use App\Models\EvaluationRevision;
use App\Models\EvaluationScore;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EvaluationExportController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', EvaluationExport::class);

        return view('panel.evaluations.exports.create', [
            'evaluationCount' => Evaluation::query()->count(),
            'revisionCount' => EvaluationRevision::query()->count(),
            'criterionCount' => EvaluationScore::query()->count(),
            'reopeningCount' => EvaluationReopening::query()->count(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', EvaluationExport::class);
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        if ($confirmedAt < time() - (int) config('auth.password_timeout', 10800)) {
            return redirect()->route('panel.evaluations.exports.create')
                ->with('warning', 'Confirma nuevamente tu contraseña antes de generar la exportación.');
        }

        $export = DB::transaction(function () use ($request, $audit): EvaluationExport {
            $export = new EvaluationExport;
            $export->forceFill([
                'requested_by_user_id' => $request->user()->id,
                'status' => EvaluationExportStatus::Queued,
                'scope_version' => 'all_revisions_v1',
                'disk' => config('flowerflow.exports.disk'),
            ])->save();

            $audit->record('evaluation_export.requested', $export, $request->user(), [
                'scope_version' => 'all_revisions_v1',
                'status' => EvaluationExportStatus::Queued->value,
            ]);

            return $export;
        }, 3);

        try {
            GenerateEvaluationExport::dispatch($export->id);
        } catch (Throwable $exception) {
            $failureCode = class_basename($exception);
            $export->forceFill([
                'status' => EvaluationExportStatus::Failed,
                'failed_at' => now('UTC'),
                'failure_code' => $failureCode,
            ])->save();
            $audit->record('evaluation_export.failed', $export, $request->user(), [
                'scope_version' => $export->scope_version,
                'status' => EvaluationExportStatus::Failed->value,
                'failure_code' => $failureCode,
            ]);
            report($exception);

            return redirect()->route('panel.evaluations.index')
                ->with('warning', 'No fue posible programar la exportación. Inténtalo nuevamente.');
        }

        return redirect()->route('panel.evaluations.index')
            ->with('status', 'La exportación se está generando. Actualiza esta pantalla para consultar su estado.');
    }

    public function download(Request $request, EvaluationExport $evaluationExport, AuditLogger $audit): StreamedResponse
    {
        $this->authorize('download', $evaluationExport);
        abort_if($evaluationExport->expires_at?->isPast(), 410, 'La exportación expiró. Genera una nueva.');
        abort_unless($evaluationExport->isAvailable(), 404);
        abort_unless(Storage::disk($evaluationExport->disk)->exists($evaluationExport->path), 404);

        $audit->record('evaluation_export.downloaded', $evaluationExport, $request->user(), [
            'scope_version' => $evaluationExport->scope_version,
            'evaluation_count' => $evaluationExport->evaluation_count,
            'revision_count' => $evaluationExport->revision_count,
            'criterion_count' => $evaluationExport->criterion_count,
            'reopening_count' => $evaluationExport->reopening_count,
        ]);

        $response = Storage::disk($evaluationExport->disk)->download(
            $evaluationExport->path,
            $evaluationExport->file_name,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
