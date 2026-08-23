<?php

namespace App\Enums;

enum CommunicationDeliveryStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
    case Unknown = 'unknown';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'En cola',
            self::Processing => 'Procesando',
            self::Sent => 'Aceptado por el servidor de correo',
            self::Failed => 'Fallido',
            self::Unknown => 'Resultado desconocido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function canBeForced(): bool
    {
        return in_array($this, [self::Queued, self::Failed, self::Unknown], true);
    }

    public function requiresAttention(): bool
    {
        return in_array($this, [self::Failed, self::Unknown], true);
    }
}
