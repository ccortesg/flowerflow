<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class EvaluationReopening extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evaluation reopenings are append-only.'));
        static::deleting(fn () => throw new LogicException('Evaluation reopenings cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'reason' => 'encrypted',
            'reason_plaintext_length' => 'integer',
            'reopened_at' => 'immutable_datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(EvaluationRevision::class, 'source_revision_id');
    }

    public function targetRevision(): BelongsTo
    {
        return $this->belongsTo(EvaluationRevision::class, 'target_revision_id');
    }

    public function subjectJudgeProfile(): BelongsTo
    {
        return $this->belongsTo(JudgeProfile::class, 'subject_judge_profile_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_user_id');
    }
}
