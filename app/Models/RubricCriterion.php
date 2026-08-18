<?php

namespace App\Models;

use App\Enums\RubricVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RubricCriterion extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(function (self $criterion): void {
            if ($criterion->isDirty('rubric_version_id')) {
                throw new LogicException('A rubric criterion cannot change versions.');
            }

            $status = RubricVersion::query()
                ->whereKey($criterion->getRawOriginal('rubric_version_id'))
                ->value('status');

            if ($status !== RubricVersionStatus::Draft->value) {
                throw new LogicException('Criteria from active or superseded rubric versions are immutable.');
            }
        });

        static::deleting(fn () => throw new LogicException('Rubric criteria cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'min_score' => 'decimal:4',
            'max_score' => 'decimal:4',
            'score_step' => 'decimal:4',
            'sort_order' => 'integer',
        ];
    }

    public function rubricVersion(): BelongsTo
    {
        return $this->belongsTo(RubricVersion::class);
    }
}
