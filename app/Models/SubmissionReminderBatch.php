<?php

namespace App\Models;

use App\Enums\SubmissionReminderBatchScope;
use App\Enums\SubmissionReminderBatchStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class SubmissionReminderBatch extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Submission reminder batches cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'scope' => SubmissionReminderBatchScope::class,
            'status' => SubmissionReminderBatchStatus::class,
            'eligible_count' => 'integer',
            'queued_count' => 'integer',
            'skipped_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(SubmissionReminder::class);
    }
}
