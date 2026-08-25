<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Evaluation extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evaluations may only change through evaluation actions.'));
        static::deleting(fn () => throw new LogicException('Evaluations cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'status' => EvaluationStatus::class,
            'lock_version' => 'integer',
            'started_at' => 'immutable_datetime',
        ];
    }

    public function judgeAssignment(): BelongsTo
    {
        return $this->belongsTo(JudgeAssignment::class);
    }

    public function rubricVersion(): BelongsTo
    {
        return $this->belongsTo(RubricVersion::class);
    }

    public function blindReviewPackage(): BelongsTo
    {
        return $this->belongsTo(BlindReviewPackage::class);
    }

    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(EvaluationRevision::class, 'current_revision_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(EvaluationRevision::class)->orderBy('revision_number');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function reopenings(): HasMany
    {
        return $this->hasMany(EvaluationReopening::class)->orderBy('id');
    }
}
