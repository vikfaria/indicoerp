<?php

namespace Tests\Feature;

use App\Models\CompanyFiscalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncFiscalCalendarCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_fiscal_calendar_command_generates_current_and_next_year_events(): void
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
        ]);

        CompanyFiscalProfile::create([
            'company_id' => $company->id,
            'legal_name' => 'Empresa Fiscal',
            'accounting_framework' => 'pgc_nirf',
            'fiscal_regime' => 'normal',
            'entity_classification' => 'small',
            'taxpayer_type' => 'ordinary',
            'state_of_certification' => 'not_certified',
            'software_certificate_number' => '0',
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $this->artisan('sce:sync-fiscal-calendar', [
            '--company_id' => $company->id,
            '--year' => 2026,
            '--years' => 2,
        ])->assertExitCode(0);

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

        $this->assertDatabaseHas('fiscal_calendar_events', [
            'company_id' => $company->id,
            'code' => 'IVA-2027-1',
            'obligation_type' => 'vat',
            'status' => 'pending',
        ]);
    }
}
