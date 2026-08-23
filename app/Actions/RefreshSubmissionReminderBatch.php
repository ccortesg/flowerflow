<?php

namespace App\Actions;

use App\Enums\SubmissionReminderBatchStatus;
use App\Enums\SubmissionReminderStatus;
use App\Models\SubmissionReminderBatch;
use Illuminate\Support\Facades\DB;

final class RefreshSubmissionReminderBatch
{
    public function execute(SubmissionReminderBatch $batch): SubmissionReminderBatch
    {
        return DB::transaction(function () use ($batch): SubmissionReminderBatch {
            $locked = SubmissionReminderBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $counts = $locked->reminders()
                ->selectRaw('status, COUNT(*) AS aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');
            $total = (int) $counts->sum();
            $sent = (int) ($counts[SubmissionReminderStatus::Sent->value] ?? 0);
            $failed = (int) ($counts[SubmissionReminderStatus::Failed->value] ?? 0);
            $skippedDuringDelivery = (int) ($counts[SubmissionReminderStatus::Skipped->value] ?? 0);
            $processing = (int) ($counts[SubmissionReminderStatus::Processing->value] ?? 0);
            $finished = $sent + $failed + $skippedDuringDelivery;

            $status = match (true) {
                $total === 0 => SubmissionReminderBatchStatus::Completed,
                $failed === $total => SubmissionReminderBatchStatus::Failed,
                $finished === $total && $failed > 0 => SubmissionReminderBatchStatus::CompletedWithFailures,
                $finished === $total => SubmissionReminderBatchStatus::Completed,
                $processing > 0 => SubmissionReminderBatchStatus::Processing,
                default => SubmissionReminderBatchStatus::Queued,
            };

            $locked->forceFill([
                'status' => $status,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'skipped_count' => max($locked->eligible_count - $locked->queued_count, 0) + $skippedDuringDelivery,
            ])->save();

            return $locked->fresh();
        }, 3);
    }
}
