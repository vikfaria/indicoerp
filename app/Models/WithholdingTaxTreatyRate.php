<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WithholdingTaxTreatyRate extends Model
{
    protected $fillable = [
        'code',
        'country_code',
        'country_name',
        'income_type',
        'standard_rate',
        'treaty_rate',
        'requires_residency_certificate',
        'legal_basis',
        'valid_from',
        'valid_to',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'standard_rate' => 'decimal:4',
            'treaty_rate' => 'decimal:4',
            'requires_residency_certificate' => 'boolean',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function scopeActiveAt(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->where(function (Builder $dateQuery) use ($date): void {
                $dateQuery
                    ->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', $date->toDateString());
            })
            ->where(function (Builder $dateQuery) use ($date): void {
                $dateQuery
                    ->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $date->toDateString());
            });
    }

    public function scopeForCountry(Builder $query, string $country): Builder
    {
        $normalized = self::normalizeCountryToken($country);
        if ($normalized === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $countryQuery) use ($normalized): void {
            $countryQuery
                ->whereRaw('UPPER(REPLACE(country_code, " ", "")) = ?', [$normalized])
                ->orWhereRaw('UPPER(REPLACE(country_name, " ", "")) = ?', [$normalized]);
        });
    }

    public static function normalizeCountryToken(string $country): string
    {
        $normalized = strtoupper(trim($country));
        if ($normalized === '') {
            return '';
        }

        return str_replace(
            ['Á', 'À', 'Â', 'Ã', 'Ç', 'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú', ' ', '-', '_'],
            ['A', 'A', 'A', 'A', 'C', 'E', 'E', 'I', 'O', 'O', 'O', 'U', '', '', ''],
            $normalized
        );
    }
}
