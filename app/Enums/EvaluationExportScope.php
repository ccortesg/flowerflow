<?php

namespace App\Enums;

enum EvaluationExportScope: string
{
    case CurrentRevisions = 'current_revisions_v1';
    case AllRevisions = 'all_revisions_v1';

    public function label(): string
    {
        return match ($this) {
            self::CurrentRevisions => 'Revisiones vigentes',
            self::AllRevisions => 'Historial completo',
        };
    }
}
