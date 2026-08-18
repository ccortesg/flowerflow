<?php

namespace App\Http\Controllers;

use App\Enums\BusinessRole;
use App\Models\Competition;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = request()->user();
        $roleNames = $user->getRoleNames()->values();
        $role = $roleNames->count() === 1
            ? BusinessRole::tryFrom((string) $roleNames->first())
            : null;

        if (! $role) {
            return redirect()->route('account.restricted');
        }

        if (in_array($role, [BusinessRole::Admin, BusinessRole::Reviewer], true)) {
            return redirect()->route('panel.dashboard');
        }

        if ($role === BusinessRole::Judge) {
            return redirect()->route(
                config('flowerflow.flags.evaluation') ? 'judge.dashboard' : 'account.restricted'
            );
        }

        $counts = $user->submissions()
            ->toBase()
            ->selectRaw(
                'COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS submitted',
                ['submitted']
            )
            ->first();

        $submissionCount = (int) ($counts->total ?? 0);
        $submittedCount = (int) ($counts->submitted ?? 0);
        $submissionLimit = (int) config('flowerflow.limits.submissions_per_user');
        $profileComplete = $user->profile?->isComplete() ?? false;

        $competition = Competition::query()
            ->with(['categories' => fn ($query) => $query
                ->where('active', true)
                ->orderBy('sort_order')])
            ->where('active', true)
            ->first();

        $businessTimezone = $competition?->source_timezone ?: config('flowerflow.timezone');
        $deadline = $competition?->closes_at?->copy()->timezone($businessTimezone);
        $configuredDeadline = CarbonImmutable::parse(
            config('flowerflow.submissions_close_at'),
            config('flowerflow.timezone')
        );
        $receptionOpen = (bool) config('flowerflow.flags.submissions')
            && $competition !== null
            && now()->lessThanOrEqualTo($configuredDeadline);
        $canCreateSubmission = $receptionOpen
            && $profileComplete
            && $submissionCount < $submissionLimit;

        $creationState = match (true) {
            ! $profileComplete => 'profile',
            $submissionCount >= $submissionLimit => 'limit',
            ! $receptionOpen => 'closed',
            default => 'available',
        };

        $profileName = trim(implode(' ', array_filter([
            trim((string) $user->profile?->first_names),
            trim((string) $user->profile?->last_names),
        ])));
        $displayName = $profileName !== '' ? $profileName : trim((string) $user->name);

        return view('participant.dashboard', compact(
            'canCreateSubmission',
            'competition',
            'creationState',
            'deadline',
            'displayName',
            'profileComplete',
            'receptionOpen',
            'submissionCount',
            'submissionLimit',
            'submittedCount'
        ));
    }
}
