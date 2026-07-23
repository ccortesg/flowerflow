<?php

namespace App\Enums;

enum ResidencyVerificationStatus: string
{
    case Requested = 'requested';
    case UnderReview = 'under_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Documento solicitado',
            self::UnderReview => 'En revisión',
            self::Verified => 'Residencia verificada',
            self::Rejected => 'Documento rechazado',
            self::Cancelled => 'Solicitud cancelada',
        };
    }
}
