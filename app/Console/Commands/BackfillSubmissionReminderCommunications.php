<?php

namespace App\Console\Commands;

use App\Enums\CommunicationAttemptSource;
use App\Enums\CommunicationAttemptStatus;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationType;
use App\Enums\SubmissionReminderStatus;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationDeliveryAttempt;
use App\Models\SubmissionReminder;
use App\Services\AuditLogger;
use App\Services\CommunicationMessageRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSubmissionReminderCommunications extends Command
{
    protected $signature = 'flowerflow:communications-backfill-reminders {--apply : Persist the backfill; without this option the command is read-only}';

    protected $description = 'Preview or backfill the communication ledger from reliable submission reminder records';

    public function handle(AuditLogger $audit): int
    {
        $query = SubmissionReminder::query()->whereNull('communication_delivery_id');
        $count = (clone $query)->count();
        $this->components->info(($this->option('apply') ? 'Eligible reminders' : 'Dry-run eligible reminders').": {$count}");
        if (! $this->option('apply') || $count === 0) {
            return self::SUCCESS;
        }

        $created = 0;
        $query->with('recipient')->orderBy('id')->chunkById(100, function ($reminders) use (&$created, $audit): void {
            foreach ($reminders as $reminder) {
                DB::transaction(function () use ($reminder, &$created, $audit): void {
                    $locked = SubmissionReminder::query()->with('recipient')->lockForUpdate()->findOrFail($reminder->id);
                    if ($locked->communication_delivery_id) {
                        return;
                    }

                    $fingerprint = CommunicationMessageRegistry::recipientFingerprint((string) $locked->recipient->email);
                    $sourceEventKey = hash_hmac('sha256', 'submission-reminder:'.$locked->public_id, (string) config('app.key'));
                    $templateVersion = (string) config('flowerflow.communication_ledger.template_version');
                    $idempotencyKey = hash('sha256', implode('|', [
                        CommunicationType::SubmissionDraftReminder->value,
                        $fingerprint,
                        $sourceEventKey,
                        $templateVersion,
                    ]));
                    $existing = CommunicationDelivery::query()->where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        $locked->forceFill(['communication_delivery_id' => $existing->id])->save();

                        return;
                    }

                    [$deliveryStatus, $attemptStatus] = $this->mappedStatuses($locked->status);
                    $needsContext = in_array($deliveryStatus, [
                        CommunicationDeliveryStatus::Queued,
                        CommunicationDeliveryStatus::Processing,
                        CommunicationDeliveryStatus::Failed,
                        CommunicationDeliveryStatus::Unknown,
                    ], true);
                    $delivery = new CommunicationDelivery;
                    $delivery->forceFill([
                        'idempotency_key' => $idempotencyKey,
                        'notification_type' => CommunicationType::SubmissionDraftReminder,
                        'template_version' => $templateVersion,
                        'source_event_key' => $sourceEventKey,
                        'channel' => 'email',
                        'recipient_user_id' => $locked->recipient_user_id,
                        'recipient_address' => $needsContext ? (string) $locked->recipient->email : null,
                        'recipient_mask' => CommunicationMessageRegistry::maskAddress((string) $locked->recipient->email),
                        'recipient_fingerprint' => $fingerprint,
                        'context' => $needsContext ? ['submission_reminder_id' => $locked->id] : null,
                        'related_type' => SubmissionReminder::class,
                        'related_id' => $locked->id,
                        'status' => $deliveryStatus,
                        'queue_connection' => config('flowerflow.mail.queue_connection'),
                        'queue' => config('flowerflow.mail.queue'),
                        'attempts_count' => $deliveryStatus === CommunicationDeliveryStatus::Queued ? 0 : 1,
                        'failure_stage' => $locked->failure_code ? 'historical' : null,
                        'failure_code' => $locked->failure_code,
                        'queued_at' => $locked->created_at,
                        'processing_at' => $deliveryStatus === CommunicationDeliveryStatus::Processing ? $locked->updated_at : null,
                        'sent_at' => $locked->sent_at,
                        'failed_at' => $locked->failed_at,
                        'cancelled_at' => $deliveryStatus === CommunicationDeliveryStatus::Cancelled ? $locked->updated_at : null,
                        'context_expires_at' => $deliveryStatus === CommunicationDeliveryStatus::Failed
                            ? ($locked->failed_at ?? $locked->updated_at)->copy()->addDays((int) config('flowerflow.communication_ledger.failed_context_retention_days'))
                            : null,
                        'created_at' => $locked->created_at,
                        'updated_at' => $locked->updated_at,
                    ])->save();
                    $attempt = new CommunicationDeliveryAttempt;
                    $attempt->forceFill([
                        'communication_delivery_id' => $delivery->id,
                        'attempt_number' => 1,
                        'source' => CommunicationAttemptSource::Automatic,
                        'status' => $attemptStatus,
                        'queue' => $delivery->queue,
                        'failure_stage' => $locked->failure_code ? 'historical' : null,
                        'failure_code' => $locked->failure_code,
                        'queued_at' => $locked->created_at,
                        'started_at' => $deliveryStatus === CommunicationDeliveryStatus::Queued ? null : $locked->created_at,
                        'finished_at' => in_array($attemptStatus, [CommunicationAttemptStatus::Queued, CommunicationAttemptStatus::Processing], true)
                            ? null
                            : $locked->updated_at,
                        'created_at' => $locked->created_at,
                        'updated_at' => $locked->updated_at,
                    ])->save();
                    $locked->forceFill(['communication_delivery_id' => $delivery->id])->save();
                    $audit->record('communication_delivery.backfilled', $delivery, metadata: [
                        'delivery_id' => $delivery->id,
                        'notification_type' => $delivery->notification_type->value,
                        'attempt_number' => 1,
                        'transition' => 'historical_to_'.$deliveryStatus->value,
                    ]);
                    $created++;
                }, 3);
            }
        });

        $this->components->info("Created deliveries: {$created}");

        return self::SUCCESS;
    }

    /** @return array{CommunicationDeliveryStatus, CommunicationAttemptStatus} */
    private function mappedStatuses(SubmissionReminderStatus $status): array
    {
        return match ($status) {
            SubmissionReminderStatus::Queued => [CommunicationDeliveryStatus::Queued, CommunicationAttemptStatus::Queued],
            SubmissionReminderStatus::Processing => [CommunicationDeliveryStatus::Processing, CommunicationAttemptStatus::Processing],
            SubmissionReminderStatus::Sent => [CommunicationDeliveryStatus::Sent, CommunicationAttemptStatus::Sent],
            SubmissionReminderStatus::Failed => [CommunicationDeliveryStatus::Failed, CommunicationAttemptStatus::Failed],
            SubmissionReminderStatus::Skipped => [CommunicationDeliveryStatus::Cancelled, CommunicationAttemptStatus::Cancelled],
        };
    }
}
