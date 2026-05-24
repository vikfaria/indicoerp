<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithholdingTaxRule extends Model
{
    protected $fillable = [
        'code', 'name', 'income_type', 'rate', 'applies_to',
        'is_final_tax', 'legal_basis', 'pgc_debit_account',
        'pgc_credit_account', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_final_tax' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WithholdingTaxTransaction::class, 'withholding_rule_id');
    }

    /**
     * Seed standard Mozambican withholding tax rules.
     */
    public static function seedDefaults(): void
    {
        $rules = [
            ['code' => 'IRPC-SRV-R', 'name' => 'Retenção serviços - residente', 'type' => 'services', 'rate' => 10.00, 'applies' => 'resident', 'final' => false, 'debit' => '622', 'credit' => '245'],
            ['code' => 'IRPC-SRV-NR', 'name' => 'Retenção serviços - não residente', 'type' => 'services', 'rate' => 20.00, 'applies' => 'non_resident', 'final' => true, 'debit' => '622', 'credit' => '245'],
            ['code' => 'IRPC-REND', 'name' => 'Retenção rendas', 'type' => 'rents', 'rate' => 10.00, 'applies' => 'both', 'final' => false, 'debit' => '625', 'credit' => '245'],
            ['code' => 'IRPC-ROY', 'name' => 'Retenção royalties', 'type' => 'royalties', 'rate' => 20.00, 'applies' => 'non_resident', 'final' => true, 'debit' => '622', 'credit' => '245'],
            ['code' => 'IRPC-JUR', 'name' => 'Retenção juros', 'type' => 'interest', 'rate' => 20.00, 'applies' => 'both', 'final' => true, 'debit' => '69', 'credit' => '245'],
            ['code' => 'IRPC-DIV', 'name' => 'Retenção dividendos', 'type' => 'dividends', 'rate' => 20.00, 'applies' => 'both', 'final' => true, 'debit' => '56', 'credit' => '245'],
            ['code' => 'IRPC-COM', 'name' => 'Retenção comissões', 'type' => 'commissions', 'rate' => 10.00, 'applies' => 'resident', 'final' => false, 'debit' => '622', 'credit' => '245'],
            ['code' => 'IRPC-GEST', 'name' => 'Retenção gestão - não residente', 'type' => 'management_fees', 'rate' => 20.00, 'applies' => 'non_resident', 'final' => true, 'debit' => '622', 'credit' => '245'],
            ['code' => 'IRPC-AT', 'name' => 'Retenção assistência técnica', 'type' => 'technical_assistance', 'rate' => 20.00, 'applies' => 'non_resident', 'final' => true, 'debit' => '622', 'credit' => '245'],
        ];

        foreach ($rules as $rule) {
            static::firstOrCreate(
                ['code' => $rule['code']],
                [
                    'name' => $rule['name'],
                    'income_type' => $rule['type'],
                    'rate' => $rule['rate'],
                    'applies_to' => $rule['applies'],
                    'is_final_tax' => $rule['final'],
                    'pgc_debit_account' => $rule['debit'],
                    'pgc_credit_account' => $rule['credit'],
                    'is_active' => true,
                ]
            );
        }
    }
}
