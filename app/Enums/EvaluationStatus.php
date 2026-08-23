<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';

    public function label(): string
    {
        return 'Borrador';
    }
}
