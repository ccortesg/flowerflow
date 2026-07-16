<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Submission;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('panel.dashboard', [
            'counts' => [
                'participants' => User::role('participant')->count(),
                'drafts' => Submission::where('status', 'draft')->count(),
                'submitted' => Submission::where('status', 'submitted')->count(),
                'total' => Submission::count(),
            ],
            'recent' => Submission::with(['user', 'category'])->latest()->limit(8)->get(),
            'categoryDistribution' => Category::query()->withCount(['submissions' => fn ($q) => $q->where('status', 'submitted')])->orderBy('sort_order')->get(),
        ]);
    }
}
