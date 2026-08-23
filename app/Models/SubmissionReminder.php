<?php

namespace App\Models;

use App\Enums\SubmissionReminderStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SubmissionReminder extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Submission reminders cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'status' => SubmissionReminderStatus::class,
            'link_expires_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SubmissionReminderBatch::class, 'submission_reminder_batch_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function communicationDelivery(): BelongsTo
    {
        return $this->belongsTo(CommunicationDelivery::class);
    }
}
