<?php

namespace App\Services;

use App\Actions\RefreshSubmissionReminderBatch;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationType;
use App\Enums\SubmissionReminderStatus;
use App\Models\CommunicationDelivery;
use App\Models\SubmissionReminder;

final class CommunicationDeliveryStateSynchronizer
{
    public function __construct(
        private RefreshSubmissionReminderBatch $refreshBatch,
        private AuditLogger $audit,
    ) {}

    public function sync(CommunicationDelivery $delivery): void
    {
        if ($delivery->notification_type !== CommunicationType::SubmissionDraftReminder) {
            return;
        }

        $reminder = SubmissionReminder::query()->with('batch.requestedBy')
            ->where('communication_delivery_id', $delivery->id)
            ->first();
        if (! $reminder) {
            return;
        }

        $previous = $reminder->status;
        $attributes = match ($delivery->status) {
            CommunicationDeliveryStatus::Queued => [
                'status' => SubmissionReminderStatus::Queued,
                'failure_code' => null,
                'failed_at' => null,
            ],
            CommunicationDeliveryStatus::Processing => [
                'status' => SubmissionReminderStatus::Processing,
                'failure_code' => null,
                'failed_at' => null,
            ],
            CommunicationDeliveryStatus::Sent => [
                'status' => SubmissionReminderStatus::Sent,
                'sent_at' => $delivery->sent_at,
                'failure_code' => null,
                'failed_at' => null,
            ],
            CommunicationDeliveryStatus::Cancelled => [
                'status' => SubmissionReminderStatus::Skipped,
                'failure_code' => $delivery->failure_code,
            ],
            CommunicationDeliveryStatus::Failed, CommunicationDeliveryStatus::Unknown => [
                'status' => SubmissionReminderStatus::Failed,
                'failure_code' => $delivery->failure_code,
                'failed_at' => $delivery->failed_at ?? $delivery->unknown_at,
            ],
        };
        $reminder->forceFill($attributes)->save();

        if ($previous !== $reminder->status && $reminder->status === SubmissionReminderStatus::Sent) {
            $this->audit->record('submission_reminder.sent', $reminder, $reminder->batch->requestedBy, [
                'batch_id' => $reminder->submission_reminder_batch_id,
                'submission_id' => $reminder->submission_id,
                'reminder_id' => $reminder->id,
            ]);
        } elseif ($previous !== $reminder->status && $reminder->status === SubmissionReminderStatus::Failed) {
            $this->audit->record('submission_reminder.failed', $reminder, $reminder->batch->requestedBy, [
                'batch_id' => $reminder->submission_reminder_batch_id,
                'submission_id' => $reminder->submission_id,
                'reminder_id' => $reminder->id,
                'reason_code' => $delivery->failure_code,
            ]);
        }

        $this->refreshBatch->execute($reminder->batch);
    }
}
