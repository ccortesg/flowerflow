<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class EvaluationScore extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Evaluation scores may only change through evaluation actions.'));
        static::deleting(fn () => throw new LogicException('Evaluation scores cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'score' => 'decimal:4',
            'calculated_component' => 'decimal:4',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(EvaluationRevision::class, 'evaluation_revision_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }
}
