<?php

namespace App\Services;

use App\Enums\SubmissionReminderStatus;
use App\Models\Competition;
use App\Models\Submission;

final class SubmissionReminderPreview
{
    /** @return array{competition:?Competition,draft_count:int,sendable_count:int,skipped_count:int} */
    public function summarize(?Submission $target = null): array
    {
        $submissions = $target
            ? collect([$target->loadMissing(['competition', 'user.roles'])])
            : Submission::query()
                ->where('status', 'draft')
                ->whereHas('competition', fn ($query) => $query->where('active', true))
                ->with(['competition', 'user.roles'])
                ->orderBy('id')
                ->get();
        $cooldownStart = now('UTC')->subHours((int) config('flowerflow.submission_reminders.cooldown_hours'));
        $sendable = $submissions->filter(function (Submission $submission) use ($cooldownStart): bool {
            $roles = $submission->user->getRoleNames();
            if (! $submission->isDraft()
                || ! $submission->user->hasVerifiedEmail()
                || $roles->count() !== 1
                || $roles->first() !== 'participant') {
                return false;
            }

            return ! $submission->reminders()
                ->where('recipient_user_id', $submission->user_id)
                ->whereIn('status', [
                    SubmissionReminderStatus::Queued->value,
                    SubmissionReminderStatus::Processing->value,
                    SubmissionReminderStatus::Sent->value,
                ])
                ->where('created_at', '>=', $cooldownStart)
                ->exists();
        });
        $competition = $submissions->first()?->competition
            ?? Competition::query()->where('active', true)->first();

        return [
            'competition' => $competition,
            'draft_count' => $submissions->count(),
            'sendable_count' => $sendable->count(),
            'skipped_count' => $submissions->count() - $sendable->count(),
        ];
    }
}
