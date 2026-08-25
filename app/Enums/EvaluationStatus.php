<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';
    case Reopened = 'reopened';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Reopened => 'Reabierta',
            self::Submitted => 'Enviada',
        };
    }
}
