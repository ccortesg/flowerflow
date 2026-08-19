<?php

namespace App\Models;

use App\Enums\RubricVersionStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class RubricVersion extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(function (self $rubric): void {
            $originalStatus = RubricVersionStatus::tryFrom((string) $rubric->getRawOriginal('status'));

            if ($originalStatus !== RubricVersionStatus::Draft) {
                throw new LogicException('An active or superseded rubric version is immutable.');
            }

            if ($rubric->isDirty([
                'public_id',
                'competition_id',
                'version',
                'status',
                'active_slot',
                'created_by_user_id',
                'activated_at',
                'activated_by_user_id',
                'activation_reason',
                'superseded_at',
                'superseded_by_user_id',
            ])) {
                throw new LogicException('Rubric identity and lifecycle fields can only change through the lifecycle action.');
            }
        });

        static::deleting(fn () => throw new LogicException('Rubric versions cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'status' => RubricVersionStatus::class,
            'criterion_score_min' => 'decimal:4',
            'criterion_score_max' => 'decimal:4',
            'criterion_score_step' => 'decimal:4',
            'total_weight' => 'decimal:4',
            'total_score_min' => 'decimal:4',
            'total_score_max' => 'decimal:4',
            'internal_decimal_places' => 'integer',
            'display_decimal_places' => 'integer',
            'general_comment_min_characters' => 'integer',
            'general_comment_max_characters' => 'integer',
            'criterion_comment_max_characters' => 'integer',
            'active_slot' => 'integer',
            'activated_at' => 'immutable_datetime',
            'superseded_at' => 'immutable_datetime',
        ];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterion::class)->orderBy('sort_order');
    }

    public function judgeAssignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by_user_id');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by_user_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superseded_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === RubricVersionStatus::Draft;
    }
}
