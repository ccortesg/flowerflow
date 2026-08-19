<?php

namespace App\Enums;

enum BlindReviewPackageStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Invalidated = 'invalidated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Active => 'Activo',
            self::Invalidated => 'Invalidado',
        };
    }
}
