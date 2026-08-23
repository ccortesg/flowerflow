<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Evaluations\OpenEvaluationDraft;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Exceptions\StaleEvaluationDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveEvaluationDraftRequest;
use App\Http\Requests\StartEvaluationDraftRequest;
use App\Models\JudgeAssignment;
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

        return redirect()->route('judge.assignments.show', $judgeAssignment)
            ->with('status', 'La evaluación en borrador quedó iniciada. Aún no se ha enviado.');
    }

    public function update(
        SaveEvaluationDraftRequest $request,
        JudgeAssignment $judgeAssignment,
        SaveEvaluationDraft $save,
    ): RedirectResponse|Response {
        try {
            $save->execute($judgeAssignment, $request->user(), $request->validated());
        } catch (StaleEvaluationDraft $exception) {
            return response()->view('errors.409', [
                'assignment' => $judgeAssignment,
                'message' => $exception->getMessage(),
            ], 409);
        }

        return redirect()->route('judge.assignments.show', $judgeAssignment)
            ->with('status', 'Borrador guardado. El servidor recalculó los componentes y el total disponible.');
    }
}
