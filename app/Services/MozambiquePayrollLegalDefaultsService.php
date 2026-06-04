<?php

namespace App\Services;

use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MozambiquePayrollLegalDefaultsService
{
    public const IRPS_TABLE_NAME = 'Tabela IRPS Moçambique Oficial';
    public const IRPS_EFFECTIVE_FROM = '2025-07-01';
    public const INSS_EFFECTIVE_FROM = '2025-07-01';
    public const MINIMUM_WAGE_EFFECTIVE_FROM = '2025-07-01';

    private const SETTING_IRPS_MINIMUM_NON_TAXABLE_AMOUNT = 'mz_irps_minimum_non_taxable_amount';
    private const SETTING_IRPS_DEPENDENT_DEDUCTION_AMOUNT = 'mz_irps_dependent_deduction_amount';
    private const SETTING_IRPS_NON_RESIDENT_FLAT_RATE_PERCENT = 'mz_irps_non_resident_flat_rate_percent';

    private const DEFAULT_MINIMUM_NON_TAXABLE_AMOUNT = 18750.00;
    private const DEFAULT_DEPENDENT_DEDUCTION_AMOUNT = 150.00;
    private const DEFAULT_NON_RESIDENT_FLAT_RATE_PERCENT = 20.00;

    public const IRPS_BRACKETS = [
        ['range_from' => 0.00, 'range_to' => 3500.00, 'fixed_amount' => 0.00, 'rate_percent' => 10.00, 'sequence' => 1],
        ['range_from' => 3500.00, 'range_to' => 14000.00, 'fixed_amount' => 350.00, 'rate_percent' => 15.00, 'sequence' => 2],
        ['range_from' => 14000.00, 'range_to' => 42000.00, 'fixed_amount' => 1925.00, 'rate_percent' => 20.00, 'sequence' => 3],
        ['range_from' => 42000.00, 'range_to' => 126000.00, 'fixed_amount' => 7525.00, 'rate_percent' => 25.00, 'sequence' => 4],
        ['range_from' => 126000.00, 'range_to' => null, 'fixed_amount' => 28525.00, 'rate_percent' => 32.00, 'sequence' => 5],
    ];

    public const MINIMUM_WAGES = [
        ['sector_code' => 'S1_AGRICULTURE', 'sector_name' => 'Sector 1. Agricultura, Pecuária, Caça, Florestas e Silvicultura', 'monthly_amount' => 6688.00],
        ['sector_code' => 'S2_PESCA_INDUSTRIAL', 'sector_name' => 'Sector 2. Pesca Industrial e Semi-Industrial', 'monthly_amount' => 6726.88],
        ['sector_code' => 'S2_1_KAPENTA', 'sector_name' => '2.1. Pesca de Kapenta', 'monthly_amount' => 4991.09],
        ['sector_code' => 'S3_1_GRANDES_EMPRESAS', 'sector_name' => '3.1. Grandes Empresas - Indústria de Extração Mineira', 'monthly_amount' => 15176.66],
        ['sector_code' => 'S3_2_PEDREIRAS_AREEIRO', 'sector_name' => '3.2. Medias Empresas - Pedreiras e Areeiro', 'monthly_amount' => 8008.00],
        ['sector_code' => 'S3_3_SALINAS', 'sector_name' => '3.3. Micro e Pequenas Empresas - Salinas', 'monthly_amount' => 6538.44],
        ['sector_code' => 'S4_INDUSTRIA_TRANSFORMADORA', 'sector_name' => 'Sector 4. Indústria Transformadora', 'monthly_amount' => 10147.50],
        ['sector_code' => 'S4_1_PANIFICACAO', 'sector_name' => '4.1. Panificação', 'monthly_amount' => 7200.00],
        ['sector_code' => 'S4_2_CAJU', 'sector_name' => '4.2. Cajú', 'monthly_amount' => 6653.21],
        ['sector_code' => 'S5_1_GRANDES_EMPRESAS_ENERGIA', 'sector_name' => '5.1. Grandes Empresas - Produção e Distribuição de Electricidade, Gás e Água', 'monthly_amount' => 12275.00],
        ['sector_code' => 'S5_2_PME_ENERGIA', 'sector_name' => '5.2. Pequenas e Medias Empresas - Produção e Distribuição de Electricidade, Gás e Água', 'monthly_amount' => 9960.62],
        ['sector_code' => 'S6_CONSTRUCAO_CIVIL', 'sector_name' => 'Sector 6. Construção Civil', 'monthly_amount' => 8400.00],
        ['sector_code' => 'S7_SERVICOS_NAO_FINANCEIROS', 'sector_name' => 'Sector 7. Actividades de Serviços Não Financeiros', 'monthly_amount' => 10310.00],
        ['sector_code' => 'S7_1_HOTELARIA_TURISMO', 'sector_name' => '7.1. Hoteleira Turismo e Similares', 'monthly_amount' => 9700.00],
        ['sector_code' => 'S7_2_SEGURANCA_PRIVADA', 'sector_name' => '7.2. Segurança Privada', 'monthly_amount' => 8465.00],
        ['sector_code' => 'S7_3_RETALHISTAS_COMBUSTIVEIS', 'sector_name' => '7.3. Retalhistas de Combustíveis', 'monthly_amount' => 9739.00],
        ['sector_code' => 'S8_1_SEGURADORAS_BANCOS', 'sector_name' => '8.1. Seguradoras e Bancos', 'monthly_amount' => 19043.61],
        ['sector_code' => 'S8_2_MICRO_FINANCAS', 'sector_name' => '8.2. Micro-Finanças', 'monthly_amount' => 16764.47],
    ];

    public function seedForCompany(int $companyId): array
    {
        return DB::transaction(function () use ($companyId): array {
            $this->seedSettings($companyId);

            $irpsTable = MozIrpsTable::query()->updateOrCreate(
                [
                    'created_by' => $companyId,
                    'name' => self::IRPS_TABLE_NAME,
                ],
                [
                    'effective_from' => self::IRPS_EFFECTIVE_FROM,
                    'effective_to' => null,
                    'is_active' => true,
                ]
            );

            foreach (self::IRPS_BRACKETS as $bracket) {
                MozIrpsBracket::query()->updateOrCreate(
                    [
                        'irps_table_id' => $irpsTable->id,
                        'sequence' => $bracket['sequence'],
                    ],
                    [
                        'range_from' => $bracket['range_from'],
                        'range_to' => $bracket['range_to'],
                        'fixed_amount' => $bracket['fixed_amount'],
                        'rate_percent' => $bracket['rate_percent'],
                    ]
                );
            }

            MozIrpsBracket::query()
                ->where('irps_table_id', $irpsTable->id)
                ->whereNotIn('sequence', array_column(self::IRPS_BRACKETS, 'sequence'))
                ->delete();

            $inssRate = MozInssRate::query()->updateOrCreate(
                [
                    'created_by' => $companyId,
                    'effective_from' => self::INSS_EFFECTIVE_FROM,
                ],
                [
                    'employee_rate' => 3.0000,
                    'employer_rate' => 4.0000,
                    'effective_to' => null,
                    'is_active' => true,
                ]
            );

            foreach (self::MINIMUM_WAGES as $row) {
                MozMinimumWage::query()->updateOrCreate(
                    [
                        'created_by' => $companyId,
                        'sector_code' => $row['sector_code'],
                        'effective_from' => self::MINIMUM_WAGE_EFFECTIVE_FROM,
                    ],
                    [
                        'sector_name' => $row['sector_name'],
                        'monthly_amount' => $row['monthly_amount'],
                        'effective_to' => null,
                        'is_active' => true,
                    ]
                );
            }

            return [
                'irps_table_id' => (int) $irpsTable->id,
                'irps_brackets_count' => (int) MozIrpsBracket::query()->where('irps_table_id', $irpsTable->id)->count(),
                'inss_rate_id' => (int) $inssRate->id,
                'minimum_wages_count' => (int) MozMinimumWage::query()
                    ->where('created_by', $companyId)
                    ->where('effective_from', self::MINIMUM_WAGE_EFFECTIVE_FROM)
                    ->count(),
                'settings' => [
                    self::SETTING_IRPS_MINIMUM_NON_TAXABLE_AMOUNT => self::DEFAULT_MINIMUM_NON_TAXABLE_AMOUNT,
                    self::SETTING_IRPS_DEPENDENT_DEDUCTION_AMOUNT => self::DEFAULT_DEPENDENT_DEDUCTION_AMOUNT,
                    self::SETTING_IRPS_NON_RESIDENT_FLAT_RATE_PERCENT => self::DEFAULT_NON_RESIDENT_FLAT_RATE_PERCENT,
                ],
            ];
        });
    }

    private function seedSettings(int $companyId): void
    {
        $settings = [
            self::SETTING_IRPS_MINIMUM_NON_TAXABLE_AMOUNT => self::DEFAULT_MINIMUM_NON_TAXABLE_AMOUNT,
            self::SETTING_IRPS_DEPENDENT_DEDUCTION_AMOUNT => self::DEFAULT_DEPENDENT_DEDUCTION_AMOUNT,
            self::SETTING_IRPS_NON_RESIDENT_FLAT_RATE_PERCENT => self::DEFAULT_NON_RESIDENT_FLAT_RATE_PERCENT,
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                [
                    'key' => $key,
                    'created_by' => $companyId,
                ],
                [
                    'value' => (string) $value,
                    'is_public' => false,
                ]
            );
        }

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');
        Cache::forget('company_settings_owner:' . $companyId);
    }
}
