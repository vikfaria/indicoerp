<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SceSetupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sce_setup_command_generates_fiscal_profile_periods_and_calendar(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
        ]);

        $this->artisan('sce:setup', [
            '--company' => $company->id,
            '--year' => 2026,
            '--skip-catalog' => true,
            '--skip-import' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('company_fiscal_profiles', [
            'company_id' => $company->id,
            'accounting_framework' => 'pgc_nirf',
            'taxpayer_type' => 'ordinary',
            'state_of_certification' => 'not_certified',
        ]);

        $this->assertDatabaseHas('mz_vat_codes', [
            'code' => 'digital_services',
            'type' => 'digital',
        ]);

        $this->assertDatabaseHas('accounting_periods', [
            'company_id' => $company->id,
            'fiscal_year' => 2026,
            'period_number' => 1,
        ]);

        $this->assertDatabaseHas('fiscal_calendar_events', [
            'company_id' => $company->id,
            'code' => 'IVA-2026-1',
            'obligation_type' => 'vat',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('fiscal_calendar_events', [
            'company_id' => $company->id,
            'code' => 'SAFT-2026',
            'obligation_type' => 'saft',
            'status' => 'pending',
        ]);
    }
}
