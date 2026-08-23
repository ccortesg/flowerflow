<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            'status' => EvaluationStatus::class,
            'total_raw' => 'decimal:4',
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
}
