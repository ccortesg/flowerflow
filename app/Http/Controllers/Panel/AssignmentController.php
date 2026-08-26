<?php

namespace App\Http\Controllers\Panel;

use App\Actions\Assignments\AssignJudgesToSubmission;
use App\Actions\Assignments\CancelJudgeAssignment;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeProfileStatus;
use App\Enums\RubricVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignJudgesRequest;
use App\Http\Requests\CancelJudgeAssignmentRequest;
use App\Http\Requests\ResolveJudgeConflictRequest;
use App\Models\Category;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Services\AdministrativeJudgeEligibility;
use App\Services\JudgeAssignmentCoverage;
use App\Services\SubmissionReferenceFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(
        Request $request,
        JudgeAssignmentCoverage $coverage,
        SubmissionReferenceFilter $referenceFilter,
    ): View {
        Gate::authorize('viewAny', JudgeAssignment::class);
        $request->validate([
            'folio' => ['nullable', 'string', 'max:64'],
            'category' => ['nullable', 'string', 'max:160'],
        ]);

        $submissions = Submission::query()
            ->where('status', 'submitted')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('eligibility_reviews')
                    ->whereColumn('eligibility_reviews.submission_id', 'submissions.id')
                    ->where('eligibility_reviews.status', EligibilityReviewStatus::Admitted->value)
                    ->whereColumn('eligibility_reviews.submission_version_id', DB::raw(
                        '(SELECT submission_versions.id FROM submission_versions WHERE submission_versions.submission_id = submissions.id ORDER BY submission_versions.version DESC, submission_versions.id DESC LIMIT 1)'
                    ));
            })
            ->when($request->filled('folio'), fn ($query) => $referenceFilter->apply(
                $query,
                $request->string('folio')->toString(),
            ))
            ->when($request->filled('category'), fn ($query) => $query->whereHas(
                'category',
                fn ($category) => $category->where('slug', $request->string('category')->toString()),
            ))
            ->with(['category:id,name', 'versions' => fn ($query) => $query->orderByDesc('version'), 'eligibilityReview'])
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $submissions->getCollection()->each(function (Submission $submission) use ($coverage): void {
            $version = $submission->versions->first();
            $submission->setAttribute('assignment_coverage', $version ? $coverage->summarize($version) : null);
        });

        return view('panel.assignments.index', [
            'submissions' => $submissions,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'slug', 'name']),
        ]);
    }

    public function show(
        Submission $submission,
        JudgeAssignmentCoverage $coverage,
        AdministrativeJudgeEligibility $judgeEligibility,
    ): View {
        Gate::authorize('viewAny', JudgeAssignment::class);
        $submission->load([
            'category:id,name',
            'versions' => fn ($query) => $query->orderByDesc('version'),
            'eligibilityReview',
        ]);
        $version = $submission->versions->firstOrFail();
        abort_unless($submission->status === 'submitted'
            && $submission->eligibilityReview?->status === EligibilityReviewStatus::Admitted
            && $submission->eligibilityReview?->submission_version_id === $version->id, 404);

        $assignments = JudgeAssignment::query()
            ->where('submission_version_id', $version->id)
            ->with(['judgeProfile.user:id,name', 'rubricVersion:id,version,title', 'conflict', 'replacementAssignment', 'evaluation:id,judge_assignment_id'])
            ->orderBy('id')
            ->get();
        $conflictedJudgeIds = JudgeConflict::query()
            ->whereHas('assignment', fn ($query) => $query->where('submission_version_id', $version->id))
            ->pluck('declared_by_judge_profile_id');
        $currentJudgeIds = $assignments->where('current_slot', 1)->pluck('judge_profile_id');
        $judges = JudgeProfile::query()
            ->whereIn('status', [JudgeProfileStatus::Active, JudgeProfileStatus::PendingSetup])
            ->whereNull('max_active_assignments')
            ->with('user.roles')
            ->withCount(['assignments as active_assignments_count' => fn ($query) => $query->whereIn('status', [
                JudgeAssignmentStatus::Active->value,
                JudgeAssignmentStatus::ConflictDeclared->value,
            ])])
            ->orderBy('id')
            ->get()
            ->filter(fn (JudgeProfile $profile): bool => $judgeEligibility->isAssignable($profile))
            ->sortBy(fn (JudgeProfile $profile): array => [$judgeEligibility->sortRank($profile), $profile->id])
            ->values();

        return view('panel.assignments.show', [
            'submission' => $submission,
            'version' => $version,
            'assignments' => $assignments,
            'coverage' => $coverage->fromAssignments($assignments),
            'availableJudges' => $judges->reject(fn (JudgeProfile $profile): bool => $currentJudgeIds->contains($profile->id)),
            'replacementJudges' => $judges->reject(fn (JudgeProfile $profile): bool => $currentJudgeIds->contains($profile->id) || $conflictedJudgeIds->contains($profile->id)),
            'activeRubric' => RubricVersion::query()
                ->where('competition_id', $submission->competition_id)
                ->where('status', RubricVersionStatus::Active)
                ->withCount('criteria')
                ->first(),
            'package' => $version->blindReviewPackage,
        ]);
    }

    public function store(AssignJudgesRequest $request, Submission $submission, AssignJudgesToSubmission $assign): RedirectResponse
    {
        $result = $assign->execute(
            $submission,
            $request->user(),
            $request->input('judge_profiles'),
            $request->string('reason')->toString(),
            $request->boolean('notify_judges'),
        );

        $response = back()->with('status', sprintf(
            'Asignación manual completada: %d creadas y %d ya vigentes omitidas.',
            $result['created']->count(),
            $result['omitted'],
        ));

        return $result['notifications_skipped_pending'] > 0
            ? $response->with('warning', sprintf(
                'Se omitieron %d correos porque esos jueces aún tienen la configuración de su cuenta pendiente.',
                $result['notifications_skipped_pending'],
            ))
            : $response;
    }

    public function cancel(JudgeAssignment $judgeAssignment): View
    {
        Gate::authorize('cancel', $judgeAssignment);

        return view('panel.assignments.cancel', [
            'assignment' => $judgeAssignment->load(['judgeProfile.user:id,name', 'submissionVersion.submission']),
        ]);
    }

    public function destroy(
        CancelJudgeAssignmentRequest $request,
        JudgeAssignment $judgeAssignment,
        CancelJudgeAssignment $cancel,
    ): RedirectResponse {
        $cancel->execute($judgeAssignment, $request->user(), $request->string('reason')->toString());
        $judgeAssignment->load('submissionVersion.submission');

        return redirect()->route('panel.assignments.show', $judgeAssignment->submissionVersion->submission)
            ->with('status', 'La asignación quedó cancelada y su evidencia se conservó.');
    }

    public function resolve(ResolveJudgeConflictRequest $request, JudgeConflict $judgeConflict, ResolveJudgeConflict $resolve): RedirectResponse
    {
        $replacement = $resolve->execute(
            $judgeConflict,
            $request->user(),
            $request->string('judge_profile')->toString(),
            $request->string('reason')->toString(),
            $request->boolean('notify_judge'),
        );

        $response = back()->with('status', 'El conflicto quedó resuelto mediante una reasignación manual.');
        $replacement->loadMissing('judgeProfile');

        return $request->boolean('notify_judge')
            && $replacement->judgeProfile->status === JudgeProfileStatus::PendingSetup
            ? $response->with('warning', 'La asignación se creó, pero el correo se omitió porque el juez aún tiene la configuración de su cuenta pendiente.')
            : $response;
    }
}
