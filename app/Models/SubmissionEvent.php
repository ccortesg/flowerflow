<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'draft_created' => 'Borrador creado',
            'draft_updated' => 'Borrador actualizado',
            'file_deleted' => 'Archivo eliminado',
            'submitted' => 'Propuesta enviada',
            default => 'Actividad registrada',
        };
    }
}
