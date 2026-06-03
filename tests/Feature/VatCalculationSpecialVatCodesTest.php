<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\MzVatCode;
use App\Services\VatCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatCalculationSpecialVatCodesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_calculate_line_applies_vat_to_import_codes(): void
    {
        MzVatCode::seedDefaults();

        $result = app(VatCalculationService::class)->calculateLine(100.0, 'IMP', 'input');

        $this->assertSame(16.0, (float) data_get($result, 'vat_amount'));
        $this->assertSame(116.0, (float) data_get($result, 'total_with_vat'));
        $this->assertSame('import', (string) data_get($result, 'vat_type'));
        $this->assertSame(16.0, (float) data_get($result, 'deductible_amount'));
    }

    public function test_calculate_line_applies_vat_to_digital_codes(): void
    {
        MzVatCode::seedDefaults();

        $this->assertDatabaseHas('mz_vat_codes', [
            'code' => 'digital_services',
            'type' => 'digital',
        ]);

        $result = app(VatCalculationService::class)->calculateLine(200.0, 'digital_services', 'output');

        $this->assertSame(32.0, (float) data_get($result, 'vat_amount'));
        $this->assertSame(232.0, (float) data_get($result, 'total_with_vat'));
        $this->assertSame('digital', (string) data_get($result, 'vat_type'));
    }

    public function test_reverse_charge_input_line_self_assesses_vat_and_keeps_full_deductibility(): void
    {
        MzVatCode::seedDefaults();

        $result = app(VatCalculationService::class)->calculateLine(150.0, 'AUT', 'input');

        $this->assertSame(24.0, (float) data_get($result, 'vat_amount'));
        $this->assertSame(174.0, (float) data_get($result, 'total_with_vat'));
        $this->assertSame('reverse_charge', (string) data_get($result, 'vat_type'));
        $this->assertSame(24.0, (float) data_get($result, 'deductible_amount'));
        $this->assertTrue((bool) data_get($result, 'is_deductible'));
    }
}
