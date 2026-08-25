<?php

namespace App\Http\Controllers\Panel;

use App\Enums\SubmissionExportStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Submission;
use App\Services\SubmissionContentSanitizer;
use App\Services\SubmissionReferenceFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request, SubmissionReferenceFilter $referenceFilter): View
    {
        $request->validate([
            'folio' => ['nullable', 'string', 'max:64'],
        ]);

        $submissions = Submission::query()->with(['user', 'category', 'eligibilityReview'])
            ->when($request->filled('folio'), fn ($query) => $referenceFilter->apply($query, $request->string('folio')->toString()))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('category'), fn ($q, $category) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $category)))
            ->latest()->paginate(25)->withQueryString();

        $categories = Category::query()->orderBy('sort_order')->get();
        $exports = $request->user()->can('export submissions')
            ? $request->user()->submissionExports()->latest()->limit(5)->get()
            : collect();
        $staleBefore = now()->subMinutes((int) config('flowerflow.exports.stalled_after_minutes'));
        $hasStalledExports = $exports->contains(
            fn ($export) => $export->status === SubmissionExportStatus::Queued
                && $export->created_at->lessThanOrEqualTo($staleBefore),
        );

        return view('panel.submissions.index', compact('submissions', 'categories', 'exports', 'hasStalledExports'));
    }

    public function show(Submission $submission, SubmissionContentSanitizer $sanitizer): View
    {
        $submission->load(['user.profile', 'category', 'competition', 'team.members', 'files', 'externalLinks', 'versions', 'events']);
        $safeHtml = $sanitizer->sanitize($submission->description_html ?? '');

        return view('panel.submissions.show', compact('submission', 'safeHtml'));
    }
}
