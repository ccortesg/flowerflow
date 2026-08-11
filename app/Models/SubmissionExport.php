<?php

namespace App\Models;

use App\Enums\SubmissionExportStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
