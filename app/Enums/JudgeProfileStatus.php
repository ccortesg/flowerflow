<?php

namespace App\Enums;

enum JudgeProfileStatus: string
{
    case PendingSetup = 'pending_setup';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PendingSetup => 'Configuración pendiente',
            self::Active => 'Activo',
            self::Suspended => 'Suspendido',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
