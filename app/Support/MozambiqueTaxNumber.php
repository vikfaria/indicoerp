<?php

namespace App\Support;

class MozambiqueTaxNumber
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value) ?? '';
        $normalized = trim($normalized);

        return $normalized === '' ? null : $normalized;
    }

    public static function isValidNuit(?string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return false;
        }

        return (bool) preg_match('/^\d{9}$/', $normalized);
    }

    public static function isMozambiqueCountry(?string $country): bool
    {
        if ($country === null) {
            return false;
        }

        $normalized = mb_strtolower(trim($country));
        $normalized = str_replace(['á', 'à', 'â', 'ã', 'ç', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú'], ['a', 'a', 'a', 'a', 'c', 'e', 'e', 'i', 'o', 'o', 'o', 'u'], $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return in_array($normalized, [
            'mz',
            'moz',
            'mozambique',
            'mocambique',
            'republic of mozambique',
        ], true);
    }
}
