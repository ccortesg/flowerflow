<?php

namespace App\Models;

use App\Enums\ClarificationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClarificationRequest extends Model
{
    use HasPublicId;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ClarificationStatus::class,
            'due_at' => 'datetime',
            'answered_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(EligibilityReview::class, 'eligibility_review_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ClarificationResponse::class);
    }

    public function residencyRequests(): HasMany
    {
        return $this->hasMany(ResidencyDocumentRequest::class);
    }
}
