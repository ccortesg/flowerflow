<?php

namespace App\Http\Controllers\Judge;

use App\Enums\JudgeAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Models\JudgeAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $base = JudgeAssignment::query()
            ->where('judge_profile_id', $request->user()->judgeProfile->id);
        $active = (clone $base)->where('status', JudgeAssignmentStatus::Active)->count();
        $notStarted = (clone $base)->where('status', JudgeAssignmentStatus::Active)
            ->whereDoesntHave('evaluation')->count();
        $drafts = (clone $base)->where('status', JudgeAssignmentStatus::Active)
            ->whereHas('evaluation')->count();
        $dueSoon = (clone $base)->where('status', JudgeAssignmentStatus::Active)
            ->whereBetween('due_at', [now('UTC'), now('UTC')->addHours(48)])->count();
        $nextAssignment = (clone $base)->where('status', JudgeAssignmentStatus::Active)
            ->where('due_at', '>=', now('UTC'))
            ->orderBy('due_at')
            ->first();

        return view('judge.dashboard', compact('active', 'notStarted', 'drafts', 'dueSoon', 'nextAssignment'));
    }
}
