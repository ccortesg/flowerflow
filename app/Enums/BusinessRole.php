<?php

namespace App\Enums;

enum BusinessRole: string
{
    case Participant = 'participant';
    case Reviewer = 'reviewer';
    case Judge = 'judge';
    case Admin = 'admin';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
