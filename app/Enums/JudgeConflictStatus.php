<?php

namespace App\Enums;

enum JudgeConflictStatus: string
{
    case Declared = 'declared';
    case ResolvedReassigned = 'resolved_reassigned';

    public function label(): string
    {
        return match ($this) {
            self::Declared => 'Pendiente de resolución',
            self::ResolvedReassigned => 'Resuelto con reasignación',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
