<?php

namespace App\Enums;

enum JudgeAssignmentRole: string
{
    case Primary = 'primary';
    case Substitute = 'substitute';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'Principal',
            self::Substitute => 'Sustituto',
        };
    }

    public function maxActiveAssignments(): ?int
    {
        return null;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
