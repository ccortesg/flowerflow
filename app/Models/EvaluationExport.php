<?php

namespace App\Models;

use App\Enums\EvaluationExportScope;
use App\Enums\EvaluationExportStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class EvaluationExport extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Evaluation exports cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'status' => EvaluationExportStatus::class,
            'completed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === EvaluationExportStatus::Completed
            && $this->path !== null
            && $this->expires_at?->isFuture();
    }

    public function scopeLabel(): string
    {
        return EvaluationExportScope::tryFrom((string) $this->scope_version)?->label()
            ?? 'Alcance desconocido';
    }
}
