<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalDocumentType extends Model
{
    protected $fillable = [
        'code', 'name', 'saft_document_type', 'category',
        'requires_hash', 'requires_series', 'is_credit_document',
        'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'requires_hash' => 'boolean',
            'requires_series' => 'boolean',
            'is_credit_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function series(): HasMany
    {
        return $this->hasMany(FiscalDocumentSeries::class);
    }

    /**
     * Seed the standard Mozambican fiscal document types.
     */
    public static function seedDefaults(): void
    {
        $types = [
            ['code' => 'FT', 'name' => 'Factura', 'saft' => 'FT', 'cat' => 'sales', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'FR', 'name' => 'Recibo', 'saft' => 'FR', 'cat' => 'sales', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'FS', 'name' => 'Factura Simplificada', 'saft' => 'FS', 'cat' => 'sales', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'NC', 'name' => 'Nota de Crédito', 'saft' => 'NC', 'cat' => 'sales', 'hash' => true, 'series' => true, 'credit' => true],
            ['code' => 'ND', 'name' => 'Nota de Débito', 'saft' => 'ND', 'cat' => 'sales', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'GR', 'name' => 'Guia de Remessa', 'saft' => 'GR', 'cat' => 'movements', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'GT', 'name' => 'Guia de Transporte', 'saft' => 'GT', 'cat' => 'movements', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'RC', 'name' => 'Recibo de Pagamento', 'saft' => 'RC', 'cat' => 'payments', 'hash' => false, 'series' => true, 'credit' => false],
            ['code' => 'AF', 'name' => 'Auto-Factura', 'saft' => 'AF', 'cat' => 'purchases', 'hash' => true, 'series' => true, 'credit' => false],
            ['code' => 'VD', 'name' => 'Venda a Dinheiro', 'saft' => 'VD', 'cat' => 'sales', 'hash' => true, 'series' => true, 'credit' => false],
        ];

        foreach ($types as $type) {
            static::firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'saft_document_type' => $type['saft'],
                    'category' => $type['cat'],
                    'requires_hash' => $type['hash'],
                    'requires_series' => $type['series'],
                    'is_credit_document' => $type['credit'],
                    'is_active' => true,
                ]
            );
        }
    }
}
