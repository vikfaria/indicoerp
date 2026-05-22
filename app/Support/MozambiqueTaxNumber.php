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

        return str_contains($normalized, 'mozambique') || str_contains($normalized, 'moçambique');
    }
}
