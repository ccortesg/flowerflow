<?php

namespace App\Models;

use App\Enums\JudgeConflictStatus;
use App\Enums\JudgeConflictType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class JudgeConflict extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Judge conflicts may only transition through audited actions.'));
        static::deleting(fn () => throw new LogicException('Judge conflicts cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'type' => JudgeConflictType::class,
            'status' => JudgeConflictStatus::class,
            'declared_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(JudgeAssignment::class, 'judge_assignment_id');
    }

    public function declaredByJudgeProfile(): BelongsTo
    {
        return $this->belongsTo(JudgeProfile::class, 'declared_by_judge_profile_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function replacementAssignment(): BelongsTo
    {
        return $this->belongsTo(JudgeAssignment::class, 'replacement_assignment_id');
    }
}
