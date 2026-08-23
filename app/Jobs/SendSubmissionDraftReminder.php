<?php

namespace App\Jobs;

use App\Actions\RefreshSubmissionReminderBatch;
use App\Enums\SubmissionReminderStatus;
use App\Mail\SubmissionDraftReminder;
use App\Models\SubmissionReminder;
use App\Services\AuditLogger;
use App\Services\SubmissionFinalizationEligibility;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class SendSubmissionDraftReminder implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public int $uniqueFor = 600;

    /** @var array<int, int> */
    public array $backoff;

    public function __construct(public int $submissionReminderId)
    {
        $this->tries = (int) config('flowerflow.mail.tries');
        $this->timeout = (int) config('flowerflow.mail.timeout');
        $this->backoff = config('flowerflow.mail.backoff');
        $this->onConnection(config('flowerflow.mail.queue_connection'));
        $this->onQueue(config('flowerflow.mail.queue'));
    }

    public function uniqueId(): string
    {
        return (string) $this->submissionReminderId;
    }

    public function handle(
        SubmissionFinalizationEligibility $eligibility,
        RefreshSubmissionReminderBatch $refreshBatch,
        AuditLogger $auditLogger,
    ): void {
        $reminder = SubmissionReminder::query()->with([
            'batch.requestedBy',
            'submission.competition',
            'recipient.roles',
        ])->findOrFail($this->submissionReminderId);

        if (in_array($reminder->status, [SubmissionReminderStatus::Sent, SubmissionReminderStatus::Skipped], true)) {
            return;
        }

        try {
            if (! config('flowerflow.flags.submission_reminders')
                || ! $reminder->submission->isDraft()
                || ! $reminder->recipient->hasVerifiedEmail()
                || $reminder->recipient->getRoleNames()->count() !== 1
                || $reminder->recipient->getRoleNames()->first() !== 'participant'
                || $reminder->link_expires_at->isPast()) {
                $this->skip($reminder, 'delivery_invariants_changed', $refreshBatch);

                return;
            }
            $eligibility->assertOpen($reminder->submission);
        } catch (ValidationException) {
            $this->skip($reminder, 'delivery_invariants_changed', $refreshBatch);

            return;
        }

        $reminder->forceFill([
            'status' => SubmissionReminderStatus::Processing,
            'failure_code' => null,
            'failed_at' => null,
        ])->save();
        $refreshBatch->execute($reminder->batch);

        Mail::to($reminder->recipient)->send(new SubmissionDraftReminder($reminder));

        $reminder->forceFill([
            'status' => SubmissionReminderStatus::Sent,
            'sent_at' => now('UTC'),
        ])->save();
        $auditLogger->record('submission_reminder.sent', $reminder, $reminder->batch->requestedBy, [
            'batch_id' => $reminder->submission_reminder_batch_id,
            'submission_id' => $reminder->submission_id,
            'reminder_id' => $reminder->id,
        ]);
        $refreshBatch->execute($reminder->batch);
    }

    public function failed(Throwable $exception): void
    {
        $reminder = SubmissionReminder::query()->with('batch.requestedBy')->find($this->submissionReminderId);
        if (! $reminder || $reminder->status === SubmissionReminderStatus::Sent) {
            return;
        }

        $reminder->forceFill([
            'status' => SubmissionReminderStatus::Failed,
            'failed_at' => now('UTC'),
            'failure_code' => class_basename($exception),
        ])->save();
        app(AuditLogger::class)->record('submission_reminder.failed', $reminder, $reminder->batch->requestedBy, [
            'batch_id' => $reminder->submission_reminder_batch_id,
            'submission_id' => $reminder->submission_id,
            'reminder_id' => $reminder->id,
            'reason_code' => class_basename($exception),
        ]);
        app(RefreshSubmissionReminderBatch::class)->execute($reminder->batch);
    }

    private function skip(
        SubmissionReminder $reminder,
        string $reasonCode,
        RefreshSubmissionReminderBatch $refreshBatch,
    ): void {
        $reminder->forceFill([
            'status' => SubmissionReminderStatus::Skipped,
            'failure_code' => $reasonCode,
        ])->save();
        $refreshBatch->execute($reminder->batch);
    }
}
