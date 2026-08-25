<?php

namespace App\Enums;

enum EvaluationSubmissionMode: string
{
    case Judge = 'judge';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Judge => 'Juez',
            self::Administrative => 'Administrativo',
        };
    }
}
