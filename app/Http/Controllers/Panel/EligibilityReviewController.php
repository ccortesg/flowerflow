<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EligibilityReviewStatus;
use App\Enums\ResidencyDocumentType;
use App\Enums\ResidencyVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecideEligibilityReviewRequest;
use App\Http\Requests\ResolveResidencyRequest;
use App\Http\Requests\StoreClarificationRequest;
use App\Http\Requests\StoreResidencyRequest;
use App\Models\Category;
use App\Models\ClarificationRequest;
use App\Models\EligibilityReview;
use App\Models\ResidencyDocumentRequest;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EligibilityReviewWorkflow;
use App\Support\MailDispatchStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EligibilityReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('view admissibility reviews'), 403);
        $fromUtc = $request->filled('from')
            ? CarbonImmutable::parse($request->string('from').' 00:00:00', config('flowerflow.timezone'))->utc()
            : null;
        $toUtc = $request->filled('to')
            ? CarbonImmutable::parse($request->string('to').' 23:59:59', config('flowerflow.timezone'))->utc()
            : null;

        $reviews = EligibilityReview::query()
            ->with(['submission:id,public_id,folio,title,category_id,submitted_at', 'submission.category:id,slug,name', 'reviewer:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->whereHas('submission.category', fn ($category) => $category->where('slug', $request->string('category'))))
            ->when($request->filled('folio'), fn ($query) => $query->whereHas('submission', fn ($submission) => $submission->where('folio', 'like', '%'.$request->string('folio')->trim().'%')))
            ->when($request->filled('reviewer'), fn ($query) => $query->where('reviewer_user_id', $request->integer('reviewer')))
            ->when($fromUtc, fn ($query) => $query->whereHas('submission', fn ($submission) => $submission->where('submitted_at', '>=', $fromUtc)))
            ->when($toUtc, fn ($query) => $query->whereHas('submission', fn ($submission) => $submission->where('submitted_at', '<=', $toUtc)))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('panel.admissibility.index', [
            'reviews' => $reviews,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'reviewers' => User::permission('review admissibility')->orderBy('name')->get(['id', 'name']),
            'statuses' => EligibilityReviewStatus::cases(),
        ]);
    }

    public function show(EligibilityReview $review, AuditLogger $audit): View
    {
        $this->authorize('view', $review);
        $review->load([
            'submission.user.profile',
            'submission.category',
            'submission.competition',
            'submission.team.members',
            'submission.files',
            'submission.externalLinks',
            'submissionVersion',
            'reviewer',
            'resolvedBy',
            'events.actor',
            'clarifications.requestedBy',
            'clarifications.responses.files',
            'residencyRequests.subjectUser',
            'residencyRequests.subjectTeamMember',
            'residencyRequests.documents',
            'residencyRequests.reviewedBy',
        ]);
        $audit->record('admissibility.review_viewed', $review, request()->user(), [
            'residency_requests_count' => $review->residencyRequests->count(),
            'residency_documents_count' => $review->residencyRequests->sum(fn ($request) => $request->documents->count()),
        ]);
        if ($review->residencyRequests->flatMap->documents->isNotEmpty()) {
            $audit->record('residency.documents_viewed', $review, request()->user(), [
                'documents_count' => $review->residencyRequests->flatMap->documents->count(),
            ]);
        }

        return view('panel.admissibility.show', [
            'review' => $review,
            'documentTypes' => ResidencyDocumentType::options(),
        ]);
    }

    public function start(EligibilityReview $review, EligibilityReviewWorkflow $workflow): RedirectResponse
    {
        $this->authorize('review', $review);
        $workflow->start($review, request()->user());

        return back()->with('status', 'La revisión quedó iniciada y asignada.');
    }

    public function requestClarification(
        StoreClarificationRequest $request,
        EligibilityReview $review,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        $workflow->requestClarification(
            $review,
            $request->user(),
            $request->string('message')->trim()->toString(),
            $request->input('due_at')
        );

        return $this->mailAwareResponse('La solicitud de aclaración quedó registrada y se programó la notificación.');
    }

    public function closeClarification(
        EligibilityReview $review,
        ClarificationRequest $clarification,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        abort_unless($clarification->eligibility_review_id === $review->id, 404);
        $this->authorize('close', $clarification);
        $workflow->closeClarification($clarification, request()->user());

        return back()->with('status', 'La aclaración quedó cerrada expresamente.');
    }

    public function requestResidency(
        StoreResidencyRequest $request,
        EligibilityReview $review,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        $teamMember = $request->filled('subject_team_member_id')
            ? TeamMember::query()->find($request->integer('subject_team_member_id'))
            : null;
        $clarification = $request->filled('clarification_public_id')
            ? ClarificationRequest::query()->where('public_id', $request->string('clarification_public_id'))->firstOrFail()
            : null;
        if ($clarification && $clarification->eligibility_review_id !== $review->id) {
            throw ValidationException::withMessages(['clarification_public_id' => 'La aclaración no pertenece a este expediente.']);
        }

        $workflow->requestResidency(
            $review,
            $request->user(),
            $request->string('subject_type')->toString(),
            $teamMember,
            $clarification,
            $request->string('instructions')->trim()->toString() ?: null
        );

        return $this->mailAwareResponse('La solicitud de residencia quedó registrada y se programó la notificación.');
    }

    public function markResidencyUnderReview(
        EligibilityReview $review,
        ResidencyDocumentRequest $residencyRequest,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        abort_unless($residencyRequest->eligibility_review_id === $review->id, 404);
        $this->authorize('review', $residencyRequest);
        $workflow->markResidencyUnderReview($residencyRequest, request()->user());

        return back()->with('status', 'La documentación quedó marcada como en revisión.');
    }

    public function resolveResidency(
        ResolveResidencyRequest $request,
        EligibilityReview $review,
        ResidencyDocumentRequest $residencyRequest,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        abort_unless($residencyRequest->eligibility_review_id === $review->id, 404);
        $workflow->resolveResidency(
            $residencyRequest,
            $request->user(),
            ResidencyVerificationStatus::from($request->string('residency_status')->toString()),
            $request->string('review_reason')->trim()->toString()
        );

        return back()->with('status', 'La resolución de residencia quedó guardada y auditada.');
    }

    public function decide(
        DecideEligibilityReviewRequest $request,
        EligibilityReview $review,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        $workflow->decide(
            $review,
            $request->user(),
            EligibilityReviewStatus::from($request->string('decision')->toString()),
            $request->string('participant_reason')->trim()->toString(),
            $request->string('internal_notes')->trim()->toString() ?: null
        );

        return $this->mailAwareResponse('La resolución de admisibilidad quedó guardada, auditada y lista para notificación.');
    }

    private function mailAwareResponse(string $message): RedirectResponse
    {
        $response = back()->with('status', $message);
        $status = app(MailDispatchStatus::class);

        return $status->failed() ? $response->with('warning', $status->warning()) : $response;
    }
}
