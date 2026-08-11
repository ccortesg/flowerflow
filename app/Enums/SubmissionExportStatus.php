<?php

namespace App\Enums;

enum SubmissionExportStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'En espera',
            self::Processing => 'Procesando',
            self::Completed => 'Disponible',
            self::Failed => 'Fallida',
            self::Expired => 'Expirada',
        };
    }
}
