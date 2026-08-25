<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class JudgeSetupLink extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Judge setup links may only transition through audited actions.'));
        static::deleting(fn () => throw new LogicException('Judge setup links cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'active_slot' => 'integer',
            'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'invalidated_at' => 'immutable_datetime',
        ];
    }

    public function judgeProfile(): BelongsTo
    {
        return $this->belongsTo(JudgeProfile::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
