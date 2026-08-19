<?php

namespace App\Http\Controllers\Panel;

use App\Actions\Assignments\ActivateSubmissionCoverage;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateSubmissionCoverageRequest;
use App\Http\Requests\ResolveJudgeConflictRequest;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
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
        $submission->load(['category:id,name', 'versions' => fn ($query) => $query->orderByDesc('version'), 'eligibilityReview']);
        $version = $submission->versions->firstOrFail();
        abort_unless($submission->status === 'submitted'
            && $submission->eligibilityReview?->status === EligibilityReviewStatus::Admitted
            && $submission->eligibilityReview?->submission_version_id === $version->id, 404);

        $assignments = JudgeAssignment::query()
            ->where('submission_version_id', $version->id)
            ->with(['judgeProfile.user:id,name', 'rubricVersion:id,version', 'conflict', 'replacementAssignment'])
            ->orderBy('id')
            ->get();

        return view('panel.assignments.show', [
            'submission' => $submission,
            'version' => $version,
            'assignments' => $assignments,
            'coverage' => $coverage->fromAssignments($assignments),
            'substitutes' => JudgeProfile::query()
                ->where('assignment_role', JudgeAssignmentRole::Substitute)
                ->where('status', JudgeProfileStatus::Active)
                ->with('user:id,name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function activate(ActivateSubmissionCoverageRequest $request, Submission $submission, ActivateSubmissionCoverage $activate): RedirectResponse
    {
        $activate->execute($submission, $request->user(), $request->string('reason')->toString());

        return back()->with('status', 'La cobertura quedó fijada con cuatro asignaciones principales.');
    }

    public function resolve(ResolveJudgeConflictRequest $request, JudgeConflict $judgeConflict, ResolveJudgeConflict $resolve): RedirectResponse
    {
        $resolve->execute(
            $judgeConflict,
            $request->user(),
            $request->string('substitute_judge_profile')->toString(),
            $request->string('reason')->toString(),
        );

        return back()->with('status', 'El conflicto quedó resuelto mediante una reasignación al juez sustituto.');
    }
}
