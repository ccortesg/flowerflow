<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Assignments\DeclareJudgeConflict;
use App\Actions\Evaluations\EnsureEvaluationDraftContext;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeConflictType;
use App\Exceptions\EvaluationDraftRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeclareJudgeConflictRequest;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Services\AuditLogger;
use App\Services\EvaluationDraftCalculator;
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
        AuditLogger $audit,
        EnsureEvaluationDraftContext $evaluationContext,
        EvaluationDraftCalculator $calculator,
    ): View {
        Gate::authorize('view', $judgeAssignment);
        $judgeAssignment->load([
            'submissionVersion:id,submission_id',
            'submissionVersion.submission:id,category_id',
            'submissionVersion.submission.category:id,name',
            'conflict',
            'submissionVersion.blindReviewPackage.files',
        ]);

        $package = $judgeAssignment->status === JudgeAssignmentStatus::Active
            && $judgeAssignment->submissionVersion->blindReviewPackage?->status === BlindReviewPackageStatus::Active
            ? $judgeAssignment->submissionVersion->blindReviewPackage
            : null;
        if ($package) {
            Gate::authorize('consume', [$package, $judgeAssignment]);
            $audit->record('blind_review_package.accessed', $package, request()->user(), [
                'assignment_id' => $judgeAssignment->id,
                'schema_version' => $package->schema_version,
                'payload_sha256' => $package->payload_sha256,
                'status' => $package->status->value,
            ]);
        }

        $evaluation = null;
        $canStartEvaluation = false;
        $evaluationReadOnly = false;
        $evaluationUnavailable = false;
        $evaluationTotalDisplay = null;
        if ($package && Gate::allows('viewEvaluationDraft', $judgeAssignment)) {
            $existing = Evaluation::query()
                ->where('judge_assignment_id', $judgeAssignment->id)
                ->first();
            try {
                $context = $evaluationContext->execute(
                    $judgeAssignment,
                    request()->user(),
                    $existing === null,
                    false,
                );
                if ($existing) {
                    $evaluationContext->assertAggregate($existing, $context, false);
                    $evaluation = $existing->load([
                        'rubricVersion.criteria',
                        'currentRevision.scores.criterion',
                    ]);
                    $evaluationReadOnly = now('UTC')->greaterThan($judgeAssignment->due_at);
                    $evaluationTotalDisplay = $evaluation->currentRevision->total_raw === null
                        ? null
                        : $calculator->display($evaluation->currentRevision->total_raw);
                } else {
                    $canStartEvaluation = Gate::allows('startEvaluationDraft', $judgeAssignment);
                }
            } catch (EvaluationDraftRejected) {
                $evaluationUnavailable = $existing !== null;
            }
        }

        return view('judge.assignments.show', [
            'assignment' => $judgeAssignment,
            'conflictTypes' => JudgeConflictType::cases(),
            'package' => $package,
            'evaluation' => $evaluation,
            'canStartEvaluation' => $canStartEvaluation,
            'evaluationReadOnly' => $evaluationReadOnly,
            'evaluationUnavailable' => $evaluationUnavailable,
            'evaluationTotalDisplay' => $evaluationTotalDisplay,
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
