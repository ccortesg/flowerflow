<?php

namespace App\Models;

use App\Enums\EligibilityReviewStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EligibilityReview extends Model
{
    use HasPublicId;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => EligibilityReviewStatus::class,
            'internal_notes' => 'encrypted',
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function submissionVersion(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EligibilityReviewEvent::class);
    }

    public function clarifications(): HasMany
    {
        return $this->hasMany(ClarificationRequest::class);
    }

    public function residencyRequests(): HasMany
    {
        return $this->hasMany(ResidencyDocumentRequest::class);
    }
}
