<?php

namespace App\Http\Controllers\Judge;

use App\Http\Controllers\Controller;
use App\Models\JudgeAssignment;
use App\Services\JudgeEvaluationWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function show(JudgeAssignment $judgeAssignment, JudgeEvaluationWorkspace $workspace): View|RedirectResponse
    {
        Gate::authorize('view', $judgeAssignment);
        $judgeAssignment->load([
            'submissionVersion:id,submission_id',
            'submissionVersion.submission:id,category_id',
            'submissionVersion.submission.category:id,name',
            'conflict',
            'submissionVersion.blindReviewPackage:id,submission_version_id,status',
        ]);
        $state = $workspace->inspect($judgeAssignment, request()->user());

        if (! $state['evaluation']) {
            if ($state['evaluationUnavailable']) {
                abort(in_array($state['evaluationUnavailableReason'], [
                    'actor_not_authorized',
                    'assignment_owner_not_operational',
                    'assignment_not_active',
                ], true) ? 403 : 409, 'La evaluación no está disponible con las condiciones actuales.');
            }

            return redirect()->route('judge.assignments.show', $judgeAssignment)
                ->with('status', 'Primero inicia explícitamente la evaluación.');
        }

        return view('judge.assignments.evaluation', [
            'assignment' => $judgeAssignment,
            ...$state,
            'finalizationEnabled' => config('flowerflow.flags.evaluation_finalization') === true,
        ]);
    }
}
