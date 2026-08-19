<?php

namespace App\Enums;

enum JudgeAssignmentStatus: string
{
    case Active = 'active';
    case ConflictDeclared = 'conflict_declared';
    case Voided = 'voided';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::ConflictDeclared => 'Conflicto declarado',
            self::Voided => 'Anulada',
            self::Cancelled => 'Cancelada',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
