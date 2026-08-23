<?php

namespace App\Models;

use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class CommunicationDelivery extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Communication deliveries cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'notification_type' => CommunicationType::class,
            'recipient_address' => 'encrypted',
            'context' => 'encrypted:array',
            'status' => CommunicationDeliveryStatus::class,
            'attempts_count' => 'integer',
            'lock_version' => 'integer',
            'queued_at' => 'immutable_datetime',
            'processing_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'unknown_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'context_expires_at' => 'immutable_datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CommunicationDeliveryAttempt::class);
    }
}
