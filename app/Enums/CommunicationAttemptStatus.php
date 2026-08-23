<?php

namespace App\Enums;

enum CommunicationAttemptStatus: string
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
            self::Sent => 'Aceptado',
            self::Failed => 'Fallido',
            self::Unknown => 'Desconocido',
            self::Cancelled => 'Cancelado',
        };
    }
}
