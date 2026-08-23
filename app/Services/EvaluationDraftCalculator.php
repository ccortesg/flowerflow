<?php

namespace App\Services;

use App\Support\Decimal;
use InvalidArgumentException;

final class EvaluationDraftCalculator
{
    public function normalizeScore(mixed $score): ?string
    {
        if ($score === null || $score === '') {
            return null;
        }

        if (! is_string($score) && ! is_int($score)) {
            throw new InvalidArgumentException('El puntaje debe enviarse como decimal simple.');
        }

        $score = (string) $score;
        if (preg_match('/^(?:0|[1-9]\d?)(?:\.\d{1,4})?$/D', $score) !== 1
            || bccomp($score, '0.0000', 4) < 0
            || bccomp($score, '10.0000', 4) > 0) {
            throw new InvalidArgumentException('El puntaje debe estar entre 0.0000 y 10.0000 sin notación científica.');
        }

        $scaledUnits = bcmul($score, '10000', 0);
        if (bccomp(bcmod($scaledUnits, '5000', 0), '0', 0) !== 0) {
            throw new InvalidArgumentException('El puntaje debe avanzar en pasos exactos de 0.5000.');
        }

        return bcadd($score, '0', 4);
    }

    public function component(string $score, string $weight): string
    {
        $normalized = $this->normalizeScore($score);
        if ($normalized === null || preg_match('/^\d+(?:\.\d{1,4})?$/D', $weight) !== 1) {
            throw new InvalidArgumentException('No se puede calcular el componente decimal.');
        }

        return bcmul(bcdiv($normalized, '10.0000', 8), $weight, 4);
    }

    /** @param list<string|null> $components */
    public function total(array $components): ?string
    {
        if (count($components) !== 5 || in_array(null, $components, true)) {
            return null;
        }

        $total = '0.0000';
        foreach ($components as $component) {
            $total = bcadd($total, (string) $component, 4);
        }

        return $total;
    }

    public function display(string $value): string
    {
        return Decimal::roundHalfUp($value, 2);
    }
}
