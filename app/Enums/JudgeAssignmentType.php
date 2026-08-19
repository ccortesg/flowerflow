<?php

namespace App\Enums;

enum JudgeAssignmentType: string
{
    case Initial = 'initial';
    case Replacement = 'replacement';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Inicial',
            self::Replacement => 'Sustitución',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
