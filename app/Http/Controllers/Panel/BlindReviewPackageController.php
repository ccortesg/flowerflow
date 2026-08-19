<?php

namespace App\Http\Controllers\Panel;

use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Enums\EligibilityReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateBlindReviewPackageRequest;
use App\Http\Requests\GenerateBlindReviewPackageRequest;
use App\Models\BlindReviewPackage;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BlindReviewPackageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', BlindReviewPackage::class);
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
            ->with([
                'category:id,name',
                'versions' => fn ($query) => $query->orderByDesc('version')->with('blindReviewPackage:id,submission_version_id,status,payload_sha256'),
            ])
            ->orderBy('id')
            ->paginate(20);

        return view('panel.blind-review-packages.index', compact('submissions'));
    }

    public function show(Submission $submission): View
    {
        Gate::authorize('viewAny', BlindReviewPackage::class);
        $submission->load([
            'category:id,name',
            'versions' => fn ($query) => $query->orderByDesc('version'),
            'eligibilityReview',
        ]);
        $version = $submission->versions->firstOrFail();
        abort_unless($submission->status === 'submitted'
            && $submission->eligibilityReview?->status === EligibilityReviewStatus::Admitted
            && $submission->eligibilityReview?->submission_version_id === $version->id, 404);

        $package = BlindReviewPackage::query()
            ->where('submission_version_id', $version->id)
            ->with('files')
            ->first();
        if ($package) {
            Gate::authorize('view', $package);
        }

        return view('panel.blind-review-packages.show', compact('submission', 'version', 'package'));
    }

    public function generate(
        GenerateBlindReviewPackageRequest $request,
        Submission $submission,
        GenerateBlindReviewPackageDraft $generate,
    ): RedirectResponse {
        $generate->execute($submission, $request->user(), $request->string('reason')->toString());

        return back()->with('status', 'El borrador del paquete ciego quedó generado y validado.');
    }

    public function activate(
        ActivateBlindReviewPackageRequest $request,
        Submission $submission,
        ActivateBlindReviewPackage $activate,
    ): RedirectResponse {
        $activate->execute($submission, $request->user(), $request->string('reason')->toString());

        return back()->with('status', 'El paquete ciego quedó activo e inmutable.');
    }
}
