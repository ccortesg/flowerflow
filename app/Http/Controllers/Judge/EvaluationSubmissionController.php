<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Evaluations\EnsureEvaluationDraftContext;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Exceptions\StaleEvaluationDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitEvaluationRequest;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Services\EvaluationDraftCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationSubmissionController extends Controller
{
    public function confirm(JudgeAssignment $judgeAssignment, EnsureEvaluationDraftContext $ensureContext, EvaluationDraftCalculator $calculator): View
    {
        Gate::authorize('submitEvaluation', $judgeAssignment);
        $context = $ensureContext->execute($judgeAssignment, request()->user(), false, false, 'submit own evaluations');
        $evaluation = Evaluation::query()->where('judge_assignment_id', $judgeAssignment->id)->firstOrFail();
        $ensureContext->assertAggregate($evaluation, $context, false,
            [EvaluationStatus::Draft, EvaluationStatus::Reopened], [EvaluationRevisionStatus::Draft]);
        $evaluation->load(['rubricVersion.criteria', 'currentRevision.scores.criterion']);

        return view('judge.evaluations.confirm', [
            'assignment' => $judgeAssignment,
            'evaluation' => $evaluation,
            'revision' => $evaluation->currentRevision,
            'totalDisplay' => $evaluation->currentRevision->total_raw === null ? null : $calculator->display($evaluation->currentRevision->total_raw),
            'finalizationEnabled' => config('flowerflow.flags.evaluation_finalization') === true,
        ]);
    }

    public function submit(SubmitEvaluationRequest $request, JudgeAssignment $judgeAssignment, SubmitEvaluation $submit): RedirectResponse|Response
    {
        try {
            $submit->execute($judgeAssignment, $request->user(), $request->validated());
        } catch (StaleEvaluationDraft $exception) {
            return response()->view('errors.409', ['assignment' => $judgeAssignment, 'message' => $exception->getMessage()], 409);
        }

        return redirect()->route('judge.assignments.show', $judgeAssignment)
            ->with('status', 'La evaluación quedó enviada y sellada. La revisión enviada es inmutable.');
    }
}
