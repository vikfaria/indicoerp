<?php

namespace Tests\Feature;

use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use App\Models\User;
use App\Services\MozambiquePayrollTaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MozambiquePayrollTaxServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_calculates_irps_inss_and_minimum_wage_from_effective_company_configuration(): void
    {
        $company = $this->makeCompany();

        $irpsTable = MozIrpsTable::create([
            'name' => 'Tabela IRPS Teste',
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        MozIrpsBracket::insert([
            [
                'irps_table_id' => $irpsTable->id,
                'range_from' => 0,
                'range_to' => 10000,
                'fixed_amount' => 0,
                'rate_percent' => 0,
                'sequence' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'irps_table_id' => $irpsTable->id,
                'range_from' => 10000,
                'range_to' => 50000,
                'fixed_amount' => 0,
                'rate_percent' => 10,
                'sequence' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'irps_table_id' => $irpsTable->id,
                'range_from' => 50000,
                'range_to' => null,
                'fixed_amount' => 4000,
                'rate_percent' => 20,
                'sequence' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        MozInssRate::create([
            'employee_rate' => 3.0000,
            'employer_rate' => 4.0000,
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        MozMinimumWage::create([
            'sector_code' => 'GENERAL',
            'sector_name' => 'Geral',
            'monthly_amount' => 10000,
            'effective_from' => now()->startOfYear()->toDateString(),
            'effective_to' => null,
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $service = app(MozambiquePayrollTaxService::class);

        $irps = $service->calculateIrps(60000, $company->id, now()->toDateString());
        $inss = $service->calculateInss(60000, $company->id, now()->toDateString());
        $minimumWage = $service->validateMinimumWage('GENERAL', 9000, $company->id, now()->toDateString());

        $this->assertTrue($irps['configured']);
        $this->assertSame(60000.0, $irps['taxable_income']);
        $this->assertSame(6000.0, $irps['irps_amount']);

        $this->assertTrue($inss['configured']);
        $this->assertSame(3.0, $inss['employee_rate']);
        $this->assertSame(4.0, $inss['employer_rate']);
        $this->assertSame(1800.0, $inss['employee_amount']);
        $this->assertSame(2400.0, $inss['employer_amount']);

        $this->assertTrue($minimumWage['configured']);
        $this->assertSame(10000.0, $minimumWage['minimum_required']);
        $this->assertFalse($minimumWage['is_compliant']);
        $this->assertSame(1000.0, $minimumWage['gap']);
    }

    public function test_service_falls_back_to_default_inss_rates_when_not_configured(): void
    {
        $service = app(MozambiquePayrollTaxService::class);

        $inss = $service->calculateInss(10000, null, now()->toDateString());

        $this->assertFalse($inss['configured']);
        $this->assertSame(3.0, $inss['employee_rate']);
        $this->assertSame(4.0, $inss['employer_rate']);
        $this->assertSame(300.0, $inss['employee_amount']);
        $this->assertSame(400.0, $inss['employer_amount']);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }
}
