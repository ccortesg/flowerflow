<?php

namespace App\Enums;

enum EvaluationRevisionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Submitted => 'Enviada',
        };
    }
}
