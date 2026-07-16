<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionExternalLink extends Model
{
    protected $guarded = [];

    public function kindLabel(): string
    {
        return match ($this->kind) {
            'youtube' => 'Video de YouTube',
            'public_folder' => 'Carpeta pública',
            default => 'Enlace externo',
        };
    }
}
