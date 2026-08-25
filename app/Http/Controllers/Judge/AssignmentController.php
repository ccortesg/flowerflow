<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Assignments\DeclareJudgeConflict;
use App\Enums\JudgeConflictType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeclareJudgeConflictRequest;
use App\Models\JudgeAssignment;
use App\Services\JudgeEvaluationWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', JudgeAssignment::class);
        $assignments = JudgeAssignment::query()
            ->where('judge_profile_id', request()->user()->judgeProfile->id)
            ->with([
                'submissionVersion:id,submission_id',
                'submissionVersion.submission:id,category_id',
                'submissionVersion.submission.category:id,name',
                'submissionVersion.blindReviewPackage:id,submission_version_id,status',
                'rubricVersion.criteria:id,rubric_version_id,sort_order',
                'conflict',
                'evaluation.currentRevision.scores:id,evaluation_revision_id,score',
            ])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->paginate(20);

        return view('judge.assignments.index', compact('assignments'));
    }

    public function show(
        JudgeAssignment $judgeAssignment,
        JudgeEvaluationWorkspace $workspace,
    ): View {
        Gate::authorize('view', $judgeAssignment);
        $judgeAssignment->load([
            'submissionVersion:id,submission_id',
            'submissionVersion.submission:id,category_id',
            'submissionVersion.submission.category:id,name',
            'conflict',
            'submissionVersion.blindReviewPackage:id,submission_version_id,status',
        ]);
        $state = $workspace->inspect($judgeAssignment, request()->user());

        return view('judge.assignments.show', [
            'assignment' => $judgeAssignment,
            'conflictTypes' => JudgeConflictType::cases(),
            ...$state,
            'finalizationEnabled' => config('flowerflow.flags.evaluation_finalization') === true,
        ]);
    }

    public function declare(DeclareJudgeConflictRequest $request, JudgeAssignment $judgeAssignment, DeclareJudgeConflict $declare): RedirectResponse
    {
        $declare->execute(
            $judgeAssignment,
            $request->user(),
            JudgeConflictType::from($request->string('type')->toString()),
            $request->input('explanation'),
        );

        return back()->with('status', 'El conflicto quedó declarado y la asignación se bloqueó hasta su resolución.');
    }
}
