<?php

namespace App\Http\Controllers\Panel;

use App\Actions\Assignments\ExecuteBulkJudgeAssignment;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeProfileStatus;
use App\Exceptions\BulkJudgeAssignmentRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBulkJudgeAssignmentsRequest;
use App\Http\Requests\StoreBulkJudgeAssignmentsRequest;
use App\Models\Category;
use App\Models\JudgeProfile;
use App\Models\Submission;
use App\Services\BulkJudgeAssignmentEligibility;
use App\Services\BulkJudgeAssignmentIntent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class BulkAssignmentController extends Controller
{
    public function create(Request $request, BulkJudgeAssignmentEligibility $eligibility): View
    {
        $eligibility->assertAdministrator($request->user());
        $judges = JudgeProfile::query()
            ->with('user.roles')
            ->withCount(['assignments as active_assignments_count' => fn (Builder $query) => $query->whereIn('status', ['active', 'conflict_declared'])])
            ->orderBy('id')
            ->get()
            ->filter(function (JudgeProfile $profile) use ($eligibility): bool {
                try {
                    $eligibility->findEligibleJudge($profile->public_id);

                    return true;
                } catch (BulkJudgeAssignmentRejected) {
                    return false;
                }
            })
            ->sortBy(fn (JudgeProfile $profile): array => [
                $profile->status === JudgeProfileStatus::Active ? 0 : 1,
                $profile->id,
            ])
            ->values();
        $selectedJudge = null;
        if ($request->filled('judge_profile')) {
            try {
                $selectedJudge = $eligibility->findEligibleJudge($request->string('judge_profile')->toString());
            } catch (BulkJudgeAssignmentRejected) {
                abort(404);
            }
        }

        $submissions = $this->submissionQuery($request, $selectedJudge)
            ->paginate(20)
            ->withQueryString();
        $submissions->getCollection()->each(function (Submission $submission) use ($eligibility, $selectedJudge): void {
            $snapshot = $eligibility->snapshot($submission, $selectedJudge);
            $submission->setAttribute('bulk_assignment_snapshot', $snapshot);
            $submission->setAttribute('bulk_assignment_blocker', $eligibility->blocker($snapshot));
        });

        return view('panel.assignments.bulk.create', [
            'submissions' => $submissions,
            'judges' => $judges,
            'selectedJudge' => $selectedJudge,
            'categories' => Category::query()->orderBy('sort_order')->get(['id', 'slug', 'name']),
            'eligibilityStatuses' => EligibilityReviewStatus::cases(),
            'packageStatuses' => BlindReviewPackageStatus::cases(),
        ]);
    }

    public function review(
        ReviewBulkJudgeAssignmentsRequest $request,
        BulkJudgeAssignmentEligibility $eligibility,
        BulkJudgeAssignmentIntent $intents,
    ): View {
        $actor = $request->user();
        $eligibility->assertAdministrator($actor);
        try {
            $judge = $eligibility->findEligibleJudge($request->string('judge_profile')->toString());
        } catch (BulkJudgeAssignmentRejected $exception) {
            throw ValidationException::withMessages(['judge_profile' => $exception->getMessage()]);
        }
        $publicIds = array_values($request->input('submissions'));
        $submissionsByPublicId = Submission::query()
            ->whereIn('public_id', $publicIds)
            ->with([
                'category:id,name',
                'versions' => fn ($query) => $query->orderByDesc('version')->orderByDesc('id')->with('blindReviewPackage'),
                'eligibilityReview',
            ])
            ->get()
            ->keyBy('public_id');
        if ($submissionsByPublicId->count() !== count($publicIds)) {
            throw ValidationException::withMessages(['submissions' => 'Una o más propuestas seleccionadas no existen.']);
        }
        $submissions = collect($publicIds)->map(fn (string $publicId): Submission => $submissionsByPublicId->get($publicId));
        $errors = [];
        foreach ($submissions as $submission) {
            if ($blocker = $eligibility->blocker($eligibility->snapshot($submission, $judge))) {
                $errors[] = $submission->public_id.': '.$blocker['message'];
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['submissions' => $errors]);
        }

        $validated = $request->validated();
        $intent = $intents->issue($actor, $judge, $submissions, $validated);

        return view('panel.assignments.bulk.review', compact('judge', 'submissions', 'validated', 'intent'));
    }

    public function store(
        StoreBulkJudgeAssignmentsRequest $request,
        ExecuteBulkJudgeAssignment $execute,
    ): RedirectResponse {
        try {
            $result = $execute->execute($request->string('intent')->toString(), $request->user());
        } catch (BulkJudgeAssignmentRejected $exception) {
            throw ValidationException::withMessages(['intent' => $exception->getMessage()]);
        }

        return redirect()->route('panel.assignments.bulk.result')
            ->with('bulk_assignment_result', $result);
    }

    public function result(Request $request, BulkJudgeAssignmentEligibility $eligibility): View|RedirectResponse
    {
        $eligibility->assertAdministrator($request->user());
        $result = $request->session()->get('bulk_assignment_result');
        if (! is_array($result)) {
            return redirect()->route('panel.assignments.bulk.create')
                ->with('warning', 'No hay un resultado reciente para mostrar.');
        }

        return view('panel.assignments.bulk.result', compact('result'));
    }

    private function submissionQuery(Request $request, ?JudgeProfile $judge): Builder
    {
        $query = Submission::query()
            ->where('status', 'submitted')
            ->with([
                'category:id,slug,name',
                'versions' => fn ($versionQuery) => $versionQuery
                    ->orderByDesc('version')
                    ->orderByDesc('id')
                    ->with('blindReviewPackage'),
                'eligibilityReview',
            ])
            ->when($request->filled('category'), fn (Builder $item) => $item
                ->whereHas('category', fn (Builder $category) => $category->where('slug', $request->string('category'))));

        if ($request->filled('admissibility')) {
            $status = $request->string('admissibility')->toString();
            $status === 'missing'
                ? $query->whereDoesntHave('eligibilityReview')
                : $query->whereHas('eligibilityReview', fn (Builder $review) => $review->where('status', $status));
        }
        if ($request->filled('package')) {
            $status = $request->string('package')->toString();
            $latestVersionSql = '(SELECT submission_versions.id FROM submission_versions WHERE submission_versions.submission_id = submissions.id ORDER BY submission_versions.version DESC, submission_versions.id DESC LIMIT 1)';
            $method = $status === 'missing' ? 'whereNotExists' : 'whereExists';
            $query->{$method}(function ($package) use ($status, $latestVersionSql): void {
                $package->selectRaw('1')
                    ->from('blind_review_packages')
                    ->whereColumn('blind_review_packages.submission_version_id', DB::raw($latestVersionSql));
                if ($status !== 'missing') {
                    $package->where('blind_review_packages.status', $status);
                }
            });
        }
        if ($judge && $request->filled('assignment')) {
            $latestVersionSql = '(SELECT submission_versions.id FROM submission_versions WHERE submission_versions.submission_id = submissions.id ORDER BY submission_versions.version DESC, submission_versions.id DESC LIMIT 1)';
            $method = $request->string('assignment')->toString() === 'assigned' ? 'whereExists' : 'whereNotExists';
            $query->{$method}(function ($assignment) use ($judge, $latestVersionSql): void {
                $assignment->selectRaw('1')
                    ->from('judge_assignments')
                    ->whereColumn('judge_assignments.submission_version_id', DB::raw($latestVersionSql))
                    ->where('judge_assignments.judge_profile_id', $judge->id)
                    ->where('judge_assignments.current_slot', 1)
                    ->whereIn('judge_assignments.status', ['active', 'conflict_declared']);
            });
        }

        return $query->orderBy('id');
    }
}
