<?php

namespace App\Models;

use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class JudgeAssignment extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Judge assignments may only transition through audited actions.'));
        static::deleting(fn () => throw new LogicException('Judge assignments cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'type' => JudgeAssignmentType::class,
            'status' => JudgeAssignmentStatus::class,
            'current_slot' => 'integer',
            'due_at' => 'immutable_datetime',
            'assigned_at' => 'immutable_datetime',
            'voided_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function submissionVersion(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class);
    }

    public function judgeProfile(): BelongsTo
    {
        return $this->belongsTo(JudgeProfile::class);
    }

    public function rubricVersion(): BelongsTo
    {
        return $this->belongsTo(RubricVersion::class);
    }

    public function replacesAssignment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_assignment_id');
    }

    public function replacementAssignment(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_assignment_id');
    }

    public function conflict(): HasOne
    {
        return $this->hasOne(JudgeConflict::class);
    }
}
