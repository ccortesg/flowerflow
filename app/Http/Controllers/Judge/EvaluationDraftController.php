<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Evaluations\OpenEvaluationDraft;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Exceptions\StaleEvaluationDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveEvaluationDraftRequest;
use App\Http\Requests\StartEvaluationDraftRequest;
use App\Models\JudgeAssignment;
use App\Services\EvaluationDraftCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class EvaluationDraftController extends Controller
{
    public function store(
        StartEvaluationDraftRequest $request,
        JudgeAssignment $judgeAssignment,
        OpenEvaluationDraft $open,
    ): RedirectResponse {
        $open->execute($judgeAssignment, $request->user());

        return redirect()->route('judge.assignments.project.show', $judgeAssignment)
            ->with('status', 'La evaluación en borrador quedó iniciada. Aún no se ha enviado.');
    }

    public function update(
        SaveEvaluationDraftRequest $request,
        JudgeAssignment $judgeAssignment,
        SaveEvaluationDraft $save,
        EvaluationDraftCalculator $calculator,
    ): RedirectResponse|Response|JsonResponse {
        $payload = $request->validated();
        try {
            $evaluation = $save->execute($judgeAssignment, $request->user(), $payload);
        } catch (StaleEvaluationDraft $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'code' => 'stale_lock_version',
                    'reload_url' => route('judge.assignments.evaluation.show', $judgeAssignment),
                ], 409);
            }

            return response()->view('errors.409', [
                'assignment' => $judgeAssignment,
                'message' => $exception->getMessage(),
            ], 409);
        }

        if (($payload['intent'] ?? 'save') === 'autosave') {
            $evaluation->load(['rubricVersion.criteria', 'currentRevision.scores']);
            $revision = $evaluation->currentRevision;
            $captured = $revision->scores->whereNotNull('score')->count();
            $total = $evaluation->rubricVersion->criteria->count();
            $trimmedComment = preg_replace('/^\s+|\s+$/u', '', (string) $revision->general_comment) ?? '';

            return response()->json([
                'lock_version' => $evaluation->lock_version,
                'saved_at' => $revision->updated_at->timezone(config('flowerflow.timezone'))->toIso8601String(),
                'captured_criteria' => $captured,
                'total_criteria' => $total,
                'is_complete' => $revision->total_raw !== null
                    && $captured === $total
                    && mb_strlen($trimmedComment) >= 100
                    && mb_strlen($trimmedComment) <= 2000,
                'total_display' => $revision->total_raw === null ? null : $calculator->display($revision->total_raw),
            ]);
        }

        $destination = ($payload['intent'] ?? 'save') === 'review'
            ? 'judge.assignments.evaluation.confirm'
            : 'judge.assignments.evaluation.show';

        return redirect()->route($destination, $judgeAssignment)
            ->with('status', 'Borrador guardado. El servidor recalculó los componentes y el total disponible.');
    }
}
