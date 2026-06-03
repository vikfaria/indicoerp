<?php

namespace Tests\Feature;

use App\Models\ImportProcess;
use App\Models\MzVatCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProcessVatRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_process_uses_active_import_vat_code_rate(): void
    {
        MzVatCode::query()->create([
            'code' => 'IMP',
            'description' => 'IVA Importacao Parametrizado',
            'rate' => 12.50,
            'type' => 'import',
            'saft_tax_code' => 'IMP',
            'effective_from' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $import = new ImportProcess([
            'fob_value' => 1000,
            'exchange_rate' => 2,
            'freight' => 100,
            'insurance' => 50,
            'customs_duty_rate' => 5,
            'clearance_fees' => 20,
            'other_costs' => 30,
        ]);

        $import->calculateCosts();

        $this->assertSame(2000.00, (float) $import->fob_value_mzn);
        $this->assertSame(2150.00, (float) $import->cif_value);
        $this->assertSame(107.50, (float) $import->customs_duties);
        $this->assertSame(12.50, (float) $import->import_vat_rate);
        $this->assertSame(282.19, (float) $import->import_vat);
        $this->assertSame(2307.50, (float) $import->total_landed_cost);
    }

    public function test_explicit_import_vat_rate_overrides_legal_table_rate(): void
    {
        MzVatCode::query()->create([
            'code' => 'IMP',
            'description' => 'IVA Importacao',
            'rate' => 16.00,
            'type' => 'import',
            'saft_tax_code' => 'IMP',
            'is_active' => true,
        ]);

        $import = new ImportProcess([
            'fob_value' => 500,
            'exchange_rate' => 1,
            'freight' => 0,
            'insurance' => 0,
            'customs_duty_rate' => 0,
            'import_vat_rate' => 5.00,
            'clearance_fees' => 0,
            'other_costs' => 0,
        ]);

        $import->calculateCosts();

        $this->assertSame(5.00, (float) $import->import_vat_rate);
        $this->assertSame(25.00, (float) $import->import_vat);
    }

    public function test_import_process_uses_configured_fallback_when_legal_code_is_missing(): void
    {
        config(['sce.vat.default_import_rate' => 7.50]);

        $import = new ImportProcess([
            'fob_value' => 400,
            'exchange_rate' => 1,
            'freight' => 0,
            'insurance' => 0,
            'customs_duty_rate' => 0,
            'clearance_fees' => 0,
            'other_costs' => 0,
        ]);

        $import->calculateCosts();

        $this->assertSame(7.50, (float) $import->import_vat_rate);
        $this->assertSame(30.00, (float) $import->import_vat);
    }
}
