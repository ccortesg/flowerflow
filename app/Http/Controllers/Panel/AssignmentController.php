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
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Services\JudgeAssignmentCoverage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(JudgeAssignmentCoverage $coverage): View
    {
        Gate::authorize('viewAny', JudgeAssignment::class);
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
            ->with(['category:id,name', 'versions' => fn ($query) => $query->orderByDesc('version'), 'eligibilityReview'])
            ->orderBy('id')
            ->paginate(20);

        $submissions->getCollection()->each(function (Submission $submission) use ($coverage): void {
            $version = $submission->versions->first();
            $submission->setAttribute('assignment_coverage', $version ? $coverage->summarize($version) : null);
        });

        return view('panel.assignments.index', compact('submissions'));
    }

    public function show(Submission $submission, JudgeAssignmentCoverage $coverage): View
    {
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
            ->where('status', JudgeProfileStatus::Active)
            ->whereNull('max_active_assignments')
            ->with('user.roles')
            ->withCount(['assignments as active_assignments_count' => fn ($query) => $query->whereIn('status', [
                JudgeAssignmentStatus::Active->value,
                JudgeAssignmentStatus::ConflictDeclared->value,
            ])])
            ->orderBy('id')
            ->get()
            ->filter(fn (JudgeProfile $profile): bool => $profile->user?->hasExactRoles(['judge'])
                && $profile->user->hasVerifiedEmail()
                && $profile->password_initialized_at !== null);

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

        return back()->with('status', sprintf(
            'Asignación manual completada: %d creadas y %d ya vigentes omitidas.',
            $result['created']->count(),
            $result['omitted'],
        ));
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
        $resolve->execute(
            $judgeConflict,
            $request->user(),
            $request->string('judge_profile')->toString(),
            $request->string('reason')->toString(),
            $request->boolean('notify_judge'),
        );

        return back()->with('status', 'El conflicto quedó resuelto mediante una reasignación manual.');
    }
}
