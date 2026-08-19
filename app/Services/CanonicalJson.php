<?php

namespace App\Services;

final class CanonicalJson
{
    public function encode(array $value): string
    {
        return json_encode(
            $this->sort($value),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    public function hash(array $value): string
    {
        return hash('sha256', $this->encode($value));
    }

    private function sort(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sort($item);
            }
        }

        return $value;
    }
}
