<?php

namespace App\Enums;

enum BlindReviewFileClass: string
{
    case Document = 'document';
    case EditorImage = 'editor_image';

    public function label(): string
    {
        return match ($this) {
            self::Document => 'Documento',
            self::EditorImage => 'Imagen de apoyo',
        };
    }
}
