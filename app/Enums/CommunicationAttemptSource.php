<?php

namespace App\Enums;

enum CommunicationAttemptSource: string
{
    case Automatic = 'automatic';
    case AdminForced = 'admin_forced';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automático',
            self::AdminForced => 'Solicitud administrativa',
        };
    }
}
