<?php

namespace App\Support;

final class MexicoPhoneNumber
{
    public static function toE164(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (str_starts_with($digits, '52') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        return strlen($digits) === 10 ? '+52'.$digits : null;
    }

    public static function national(?string $value): string
    {
        $e164 = self::toE164($value);

        return $e164 ? substr($e164, 3) : (string) $value;
    }

    public static function formatNational(?string $value): string
    {
        $digits = self::national($value);

        if (! preg_match('/^\d{10}$/', $digits)) {
            return $digits;
        }

        return substr($digits, 0, 3).' '.substr($digits, 3, 3).' '.substr($digits, 6, 4);
    }
}
