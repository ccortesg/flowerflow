<?php

namespace App\Models;

use App\Enums\SubmissionExportKind;
use App\Enums\SubmissionExportStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ValueError;

class SubmissionExport extends Model
{
    use HasPublicId;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SubmissionExportStatus::class,
            'filters' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === SubmissionExportStatus::Completed
            && $this->path !== null
            && $this->expires_at?->isFuture();
    }

    public function kind(): SubmissionExportKind
    {
        $filters = is_array($this->filters) ? $this->filters : [];
        $kind = $filters['kind'] ?? null;

        return $kind === null
            ? SubmissionExportKind::Full
            : SubmissionExportKind::from((string) $kind);
    }

    public function kindLabel(): string
    {
        try {
            return $this->kind()->label();
        } catch (ValueError) {
            return 'Desconocida';
        }
    }

    public function kindValueForAudit(): string
    {
        try {
            return $this->kind()->value;
        } catch (ValueError) {
            return 'unknown';
        }
    }
}
