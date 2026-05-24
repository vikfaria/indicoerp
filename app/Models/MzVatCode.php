<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MzVatCode extends Model
{
    protected $fillable = [
        'code', 'description', 'rate', 'type',
        'exemption_reason', 'saft_tax_code',
        'effective_from', 'effective_to', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', now()->toDateString());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if this VAT code is taxable (has a rate > 0).
     */
    public function isTaxable(): bool
    {
        return $this->rate > 0 && $this->type === 'normal';
    }

    /**
     * Seed the standard Mozambican VAT codes.
     */
    public static function seedDefaults(): void
    {
        $codes = [
            ['code' => 'NOR', 'description' => 'IVA Taxa Normal', 'rate' => 16.00, 'type' => 'normal', 'saft_tax_code' => 'NOR'],
            ['code' => 'RED', 'description' => 'IVA Taxa Reduzida', 'rate' => 5.00, 'type' => 'normal', 'saft_tax_code' => 'RED'],
            ['code' => 'ISE', 'description' => 'Isento de IVA', 'rate' => 0.00, 'type' => 'exempt', 'saft_tax_code' => 'ISE'],
            ['code' => 'ZER', 'description' => 'IVA Taxa Zero', 'rate' => 0.00, 'type' => 'zero', 'saft_tax_code' => 'ZER'],
            ['code' => 'NSU', 'description' => 'Não sujeito a IVA', 'rate' => 0.00, 'type' => 'not_subject', 'saft_tax_code' => 'NS'],
            ['code' => 'AUT', 'description' => 'Autoliquidação / Reverse Charge', 'rate' => 16.00, 'type' => 'reverse_charge', 'saft_tax_code' => 'AUT'],
            ['code' => 'IMP', 'description' => 'IVA Importação', 'rate' => 16.00, 'type' => 'import', 'saft_tax_code' => 'IMP'],
        ];

        foreach ($codes as $code) {
            static::firstOrCreate(
                ['code' => $code['code']],
                array_merge($code, ['is_active' => true])
            );
        }
    }
}
