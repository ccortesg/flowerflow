<?php

namespace App\Actions;

use App\Enums\SubmissionReminderBatchScope;
use App\Enums\SubmissionReminderBatchStatus;
use App\Enums\SubmissionReminderStatus;
use App\Jobs\SendSubmissionDraftReminder;
use App\Models\Submission;
use App\Models\SubmissionReminder;
use App\Models\SubmissionReminderBatch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SubmissionFinalizationEligibility;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class QueueSubmissionReminders
{
    public function __construct(
        private SubmissionFinalizationEligibility $eligibility,
        private RefreshSubmissionReminderBatch $refreshBatch,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, ?Submission $target = null): SubmissionReminderBatch
    {
        $this->assertActor($actor);

        $batch = DB::transaction(function () use ($actor, $target): SubmissionReminderBatch {
            $scope = $target ? SubmissionReminderBatchScope::Single : SubmissionReminderBatchScope::AllDrafts;
            $ids = $target
                ? [$target->id]
                : Submission::query()
                    ->where('status', 'draft')
                    ->whereHas('competition', fn ($query) => $query->where('active', true))
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

            $batch = new SubmissionReminderBatch;
            $batch->forceFill([
                'requested_by_user_id' => $actor->id,
                'scope' => $scope,
                'status' => SubmissionReminderBatchStatus::Queued,
                'eligible_count' => count($ids),
            ])->save();

            $queued = 0;
            $skipped = 0;
            foreach ($ids as $id) {
                $submission = Submission::query()->lockForUpdate()->with(['competition', 'user.roles'])->findOrFail($id);
                if (! $submission->isDraft() || ! $this->recipientIsEligible($submission->user)) {
                    $skipped++;

                    continue;
                }

                $this->eligibility->assertOpen($submission);
                $cooldownStart = now('UTC')->subHours((int) config('flowerflow.submission_reminders.cooldown_hours'));
                $hasRecentReminder = SubmissionReminder::query()
                    ->where('submission_id', $submission->id)
                    ->where('recipient_user_id', $submission->user_id)
                    ->whereIn('status', [
                        SubmissionReminderStatus::Queued->value,
                        SubmissionReminderStatus::Processing->value,
                        SubmissionReminderStatus::Sent->value,
                    ])
                    ->where('created_at', '>=', $cooldownStart)
                    ->exists();
                if ($hasRecentReminder) {
                    $skipped++;

                    continue;
                }

                $configuredClose = CarbonImmutable::parse(
                    (string) config('flowerflow.submissions_close_at'),
                    (string) config('flowerflow.timezone'),
                )->utc();
                $ttlClose = CarbonImmutable::instance(now('UTC'))->addMinutes(
                    (int) config('flowerflow.submission_reminders.link_ttl_minutes'),
                );

                $reminder = new SubmissionReminder;
                $reminder->forceFill([
                    'submission_reminder_batch_id' => $batch->id,
                    'submission_id' => $submission->id,
                    'recipient_user_id' => $submission->user_id,
                    'status' => SubmissionReminderStatus::Queued,
                    'link_expires_at' => $ttlClose->lessThan($configuredClose) ? $ttlClose : $configuredClose,
                ])->save();
                $queued++;

                $this->auditLogger->record('submission_reminder.queued', $reminder, $actor, [
                    'batch_id' => $batch->id,
                    'submission_id' => $submission->id,
                    'reminder_id' => $reminder->id,
                ]);
            }

            $batch->forceFill([
                'queued_count' => $queued,
                'skipped_count' => $skipped,
                'status' => $queued > 0
                    ? SubmissionReminderBatchStatus::Queued
                    : SubmissionReminderBatchStatus::Completed,
            ])->save();
            $this->auditLogger->record('submission_reminder.batch_requested', $batch, $actor, [
                'batch_id' => $batch->id,
                'scope' => $scope->value,
                'eligible_count' => count($ids),
                'queued_count' => $queued,
                'skipped_count' => $skipped,
            ]);

            return $batch;
        }, 3);

        foreach ($batch->reminders()->pluck('id') as $reminderId) {
            try {
                SendSubmissionDraftReminder::dispatch($reminderId);
            } catch (Throwable $exception) {
                $reminder = SubmissionReminder::query()->find($reminderId);
                if ($reminder) {
                    $reminder->forceFill([
                        'status' => SubmissionReminderStatus::Failed,
                        'failed_at' => now('UTC'),
                        'failure_code' => class_basename($exception),
                    ])->save();
                    $this->auditLogger->record('submission_reminder.failed', $reminder, $actor, [
                        'batch_id' => $batch->id,
                        'submission_id' => $reminder->submission_id,
                        'reminder_id' => $reminder->id,
                        'reason_code' => class_basename($exception),
                    ]);
                }
                report($exception);
            }
        }

        return $this->refreshBatch->execute($batch);
    }

    private function assertActor(User $actor): void
    {
        $roles = $actor->getRoleNames();
        if (! config('flowerflow.flags.submission_reminders')
            || $roles->count() !== 1
            || $roles->first() !== 'admin'
            || ! $actor->can('send submission reminders')) {
            throw ValidationException::withMessages(['role' => 'La cuenta no puede enviar recordatorios de propuestas.']);
        }
    }

    private function recipientIsEligible(User $user): bool
    {
        $roles = $user->getRoleNames();

        return $user->hasVerifiedEmail() && $roles->count() === 1 && $roles->first() === 'participant';
    }
}
