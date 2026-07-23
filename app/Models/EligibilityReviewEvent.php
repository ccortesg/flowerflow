<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Models\Concerns\ImmutableRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityReviewEvent extends Model
{
    use HasPublicId, ImmutableRecord;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(EligibilityReview::class, 'eligibility_review_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function label(): string
    {
        return match ($this->event) {
            'review_created' => 'Expediente creado',
            'review_started' => 'Revisión iniciada',
            'clarification_requested' => 'Aclaración solicitada',
            'clarification_answered' => 'Aclaración respondida',
            'clarification_closed' => 'Aclaración cerrada',
            'residency_requested' => 'Comprobante de residencia solicitado',
            'residency_under_review' => 'Comprobante de residencia en revisión',
            'residency_verified' => 'Residencia verificada',
            'residency_rejected' => 'Comprobante de residencia rechazado',
            'residency_cancelled' => 'Solicitud de residencia cancelada',
            'review_admitted' => 'Propuesta admitida',
            'review_not_admitted' => 'Propuesta no admitida',
            default => 'Evento de revisión',
        };
    }
}
