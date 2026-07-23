<?php

namespace App\Enums;

enum EligibilityReviewStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case ClarificationRequested = 'clarification_requested';
    case Admitted = 'admitted';
    case NotAdmitted = 'not_admitted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de revisión',
            self::InReview => 'En revisión',
            self::ClarificationRequested => 'Requiere aclaración',
            self::Admitted => 'Admitida',
            self::NotAdmitted => 'No admitida',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Admitted, self::NotAdmitted], true);
    }
}
