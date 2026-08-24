<?php

namespace App\Enums;

enum SubmissionExportKind: string
{
    case Full = 'full';
    case SubmittedContacts = 'submitted_contacts';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Completa',
            self::SubmittedContacts => 'Contactos enviados',
        };
    }
}
