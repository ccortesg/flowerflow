<?php

namespace App\Models;

use App\Enums\CommunicationAttemptSource;
use App\Enums\CommunicationAttemptStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CommunicationDeliveryAttempt extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Communication delivery attempts cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'source' => CommunicationAttemptSource::class,
            'reason' => 'encrypted',
            'status' => CommunicationAttemptStatus::class,
            'attempt_number' => 'integer',
            'duplicate_risk_acknowledged' => 'boolean',
            'queued_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(CommunicationDelivery::class, 'communication_delivery_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
