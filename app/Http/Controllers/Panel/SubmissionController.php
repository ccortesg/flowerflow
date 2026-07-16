<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Submission;
use App\Services\SubmissionContentSanitizer;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(): View
    {
        $submissions = Submission::query()->with(['user', 'category'])
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('category'), fn ($q, $category) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $category)))
            ->latest()->paginate(25)->withQueryString();

        $categories = Category::query()->orderBy('sort_order')->get();

        return view('panel.submissions.index', compact('submissions', 'categories'));
    }

    public function show(Submission $submission, SubmissionContentSanitizer $sanitizer): View
    {
        $submission->load(['user.profile', 'category', 'competition', 'team.members', 'files', 'externalLinks', 'versions', 'events']);
        $safeHtml = $sanitizer->sanitize($submission->description_html ?? '');

        return view('panel.submissions.show', compact('submission', 'safeHtml'));
    }
}
