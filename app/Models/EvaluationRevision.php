<?php

namespace App\Models;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationSubmissionMode;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

class EvaluationRevision extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evaluation revisions may only change through evaluation actions.'));
        static::deleting(fn () => throw new LogicException('Evaluation revisions cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'status' => EvaluationRevisionStatus::class,
            'total_raw' => 'decimal:4',
            'submission_mode' => EvaluationSubmissionMode::class,
            'submitted_at' => 'immutable_datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_revision_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class)->orderBy('rubric_criterion_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lastSavedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_saved_by_user_id');
    }

    public function subjectJudgeProfile(): BelongsTo
    {
        return $this->belongsTo(JudgeProfile::class, 'subject_judge_profile_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reopeningAsSource(): HasOne
    {
        return $this->hasOne(EvaluationReopening::class, 'source_revision_id');
    }

    public function reopeningAsTarget(): HasOne
    {
        return $this->hasOne(EvaluationReopening::class, 'target_revision_id');
    }
}
