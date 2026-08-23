<?php

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    public static function roundHalfUp(string $value, int $places): string
    {
        if ($places < 0 || preg_match('/^[+-]?\d+(?:\.\d+)?$/D', $value) !== 1) {
            throw new InvalidArgumentException('A plain decimal value and non-negative precision are required.');
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $fraction = str_pad($fraction, $places + 1, '0');
        $kept = $places === 0 ? '' : substr($fraction, 0, $places);
        $scaled = ltrim($integer.$kept, '0');
        $scaled = $scaled === '' ? '0' : $scaled;

        if ((int) $fraction[$places] >= 5) {
            $scaled = bcadd($scaled, '1', 0);
        }

        if ($places === 0) {
            $result = $scaled;
        } else {
            $scaled = str_pad($scaled, $places + 1, '0', STR_PAD_LEFT);
            $result = substr($scaled, 0, -$places).'.'.substr($scaled, -$places);
        }

        return $negative && bccomp($result, '0', $places) !== 0 ? '-'.$result : $result;
    }
}
