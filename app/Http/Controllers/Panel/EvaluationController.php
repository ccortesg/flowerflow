<?php

namespace App\Http\Controllers\Panel;

use App\Actions\Evaluations\ReopenEvaluation;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\EvaluationExportStatus;
use App\Enums\EvaluationStatus;
use App\Exceptions\StaleEvaluationDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminSaveReopenedEvaluationRequest;
use App\Http\Requests\AdminSubmitEvaluationRequest;
use App\Http\Requests\ReopenEvaluationRequest;
use App\Models\Category;
use App\Models\Evaluation;
use App\Models\EvaluationExport;
use App\Services\EvaluationDraftCalculator;
use App\Services\SubmissionReferenceFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(Request $request, SubmissionReferenceFilter $referenceFilter): View
    {
        Gate::authorize('viewAny', Evaluation::class);
        $request->validate([
            'folio' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', Rule::enum(EvaluationStatus::class)],
            'category' => ['nullable', 'string', 'max:160'],
        ]);

        $evaluations = Evaluation::query()
            ->with([
                'judgeAssignment.judgeProfile.user:id,name',
                'judgeAssignment.submissionVersion.submission.category:id,name',
                'currentRevision:id,evaluation_id,revision_number,status,total_raw,last_saved_by_user_id,submitted_by_user_id,submitted_at,submission_mode',
            ])
            ->when($request->filled('folio'), fn ($query) => $query->whereHas(
                'judgeAssignment.submissionVersion.submission',
                fn ($submission) => $referenceFilter->apply($submission, $request->string('folio')->toString()),
            ))
            ->when($request->filled('status'), fn ($query) => $query->where(
                'evaluations.status',
                $request->string('status')->toString(),
            ))
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'judgeAssignment.submissionVersion.submission.category',
                fn ($category) => $category->where('slug', $request->string('category')->toString()),
            ))
            ->orderByDesc('evaluations.updated_at')
            ->paginate(25)
            ->withQueryString();

        $exports = Gate::allows('create', EvaluationExport::class)
            ? request()->user()->evaluationExports()->latest()->limit(5)->get()
            : collect();
        $staleBefore = now()->subMinutes((int) config('flowerflow.exports.stalled_after_minutes'));
        $hasStalledExports = $exports->contains(
            fn (EvaluationExport $export): bool => $export->status === EvaluationExportStatus::Queued
                && $export->created_at->lessThanOrEqualTo($staleBefore),
        );

        return view('panel.evaluations.index', [
            'evaluations' => $evaluations,
            'exports' => $exports,
            'hasStalledExports' => $hasStalledExports,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'slug', 'name']),
            'statuses' => EvaluationStatus::cases(),
        ]);
    }

    public function show(Evaluation $evaluation, EvaluationDraftCalculator $calculator): View
    {
        Gate::authorize('view', $evaluation);
        $evaluation->load($this->detailRelations());

        return view('panel.evaluations.show', [
            'evaluation' => $evaluation,
            'totalDisplay' => $evaluation->currentRevision->total_raw === null ? null : $calculator->display($evaluation->currentRevision->total_raw),
        ]);
    }

    public function reopen(Evaluation $evaluation): View
    {
        Gate::authorize('reopen', $evaluation);
        $evaluation->load(['judgeAssignment.judgeProfile.user:id,name', 'currentRevision']);

        return view('panel.evaluations.reopen', compact('evaluation'));
    }

    public function storeReopening(ReopenEvaluationRequest $request, Evaluation $evaluation, ReopenEvaluation $reopen): RedirectResponse|Response
    {
        try {
            $reopen->execute($evaluation, $request->user(), $request->integer('lock_version'), $request->string('reason')->toString());
        } catch (StaleEvaluationDraft $exception) {
            return response()->view('errors.409', ['message' => $exception->getMessage()], 409);
        }

        return redirect()->route('panel.evaluations.show', $evaluation)
            ->with('status', 'La evaluación quedó reabierta en una nueva revisión append-only.');
    }

    public function update(AdminSaveReopenedEvaluationRequest $request, Evaluation $evaluation, SaveEvaluationDraft $save): RedirectResponse|Response
    {
        $payload = $request->safe()->except('current_password');
        try {
            $save->execute($evaluation->judgeAssignment, $request->user(), $payload);
        } catch (StaleEvaluationDraft $exception) {
            return response()->view('errors.409', ['message' => $exception->getMessage()], 409);
        }
        $destination = ($payload['intent'] ?? 'save') === 'review' ? 'panel.evaluations.confirm' : 'panel.evaluations.show';

        return redirect()->route($destination, $evaluation)
            ->with('status', 'La revisión reabierta quedó guardada con el actor administrativo real.');
    }

    public function confirm(Evaluation $evaluation, EvaluationDraftCalculator $calculator): View
    {
        Gate::authorize('manageReopened', $evaluation);
        $evaluation->load($this->detailRelations());

        return view('panel.evaluations.confirm', [
            'evaluation' => $evaluation,
            'totalDisplay' => $evaluation->currentRevision->total_raw === null ? null : $calculator->display($evaluation->currentRevision->total_raw),
        ]);
    }

    public function submit(AdminSubmitEvaluationRequest $request, Evaluation $evaluation, SubmitEvaluation $submit): RedirectResponse|Response
    {
        try {
            $submit->execute($evaluation->judgeAssignment, $request->user(), $request->validated());
        } catch (StaleEvaluationDraft $exception) {
            return response()->view('errors.409', ['message' => $exception->getMessage()], 409);
        }

        return redirect()->route('panel.evaluations.show', $evaluation)
            ->with('status', 'La revisión reabierta quedó enviada. La cuenta administrativa permanece como actor real.');
    }

    private function detailRelations(): array
    {
        return [
            'judgeAssignment.judgeProfile.user:id,name', 'judgeAssignment.submissionVersion.submission.category:id,name',
            'rubricVersion.criteria', 'currentRevision.scores.criterion', 'revisions.scores.criterion',
            'revisions.createdBy:id,name', 'revisions.lastSavedBy:id,name', 'revisions.submittedBy:id,name',
            'reopenings.sourceRevision', 'reopenings.targetRevision', 'reopenings.reopenedBy:id,name',
        ];
    }
}
