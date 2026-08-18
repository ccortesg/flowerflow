<?php

namespace App\Enums;

enum RubricVersionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Active => 'Activa',
            self::Superseded => 'Sustituida',
        };
    }
}
