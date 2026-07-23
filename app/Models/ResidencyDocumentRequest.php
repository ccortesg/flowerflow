<?php

namespace App\Models;

use App\Enums\ResidencyVerificationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResidencyDocumentRequest extends Model
{
    use HasPublicId;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ResidencyVerificationStatus::class,
            'reviewed_at' => 'datetime',
            'validated_at' => 'datetime',
            'retention_basis_at' => 'datetime',
            'retention_due_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(EligibilityReview::class, 'eligibility_review_id');
    }

    public function subjectUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function subjectTeamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'subject_team_member_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function clarification(): BelongsTo
    {
        return $this->belongsTo(ClarificationRequest::class, 'clarification_request_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ResidencyDocument::class);
    }

    public function subjectLabel(): string
    {
        return $this->subjectTeamMember?->full_name ?? $this->subjectUser?->name ?? 'Persona participante';
    }
}
