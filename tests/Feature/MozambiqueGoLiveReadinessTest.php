<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\FiscalExportHistory;
use App\Models\Setting;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MozambiqueGoLiveReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_go_live_readiness_endpoint_requires_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertForbidden();
    }

    public function test_go_live_attestation_endpoint_requires_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->post(route('account.reports.mozambique-go-live-readiness.attestation'), [
            'legal_review_status' => 'approved',
        ]);

        $response->assertForbidden();
    }

    public function test_go_live_readiness_endpoint_returns_summary_and_checks(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'overall_status',
                'summary' => ['pass', 'warn', 'fail'],
                'checks' => [
                    '*' => ['code', 'label', 'status', 'critical', 'details', 'meta'],
                ],
                'formal_go_live_criteria' => [
                    'critical_checks_passed',
                    'legal_review_completed',
                    'commercial_readiness_completed',
                    'legal_tables_validation_completed',
                    'legal_tables_review_approved',
                    'pilot_completed',
                    'pilot_registry_populated',
                    'pilot_real_companies_validated',
                    'fiscal_calendar_validation_completed',
                    'payroll_sector_validation_completed',
                    'payroll_real_cases_validated',
                    'accounting_local_validation_completed',
                    'accounting_real_cases_validated',
                    'saft_submission_validation_completed',
                    'e2e_scenarios_completed',
                    'backup_restore_verified',
                    'formal_approval_granted',
                    'recommended_for_launch',
                ],
                'attestations' => [
                    'legal_review_status',
                    'legal_reviewed_at',
                    'legal_tables_validation_status',
                    'legal_tables_validation_completed_at',
                    'legal_tables_validation_notes',
                    'legal_tables_review_status',
                    'legal_tables_reviewed_at',
                    'legal_tables_review_notes',
                    'fiscal_calendar_validation_status',
                    'fiscal_calendar_validation_completed_at',
                    'fiscal_calendar_validation_notes',
                    'fiscal_calendar_export_status',
                    'fiscal_calendar_export_generated_at',
                    'fiscal_calendar_export_year',
                    'fiscal_calendar_export_file_name',
                    'fiscal_calendar_export_notes',
                    'commercial_readiness_status',
                    'commercial_reviewed_at',
                    'pilot_status',
                    'pilot_completed_at',
                    'pilot_company_count',
                    'payroll_sector_validation_status',
                    'payroll_sector_validation_completed_at',
                    'accounting_local_validation_status',
                    'accounting_local_validation_completed_at',
                    'e2e_sales_flow_status',
                    'e2e_purchase_flow_status',
                    'e2e_pos_flow_status',
                    'e2e_payroll_flow_status',
                    'e2e_completed_at',
                    'backup_restore_status',
                    'backup_restore_tested_at',
                    'backup_restore_evidence_ref',
                    'go_live_approved',
                    'go_live_approved_at',
                ],
            ]);
    }

    public function test_go_live_attestation_endpoint_persists_settings_and_updates_readiness(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $payload = [
            'legal_review_status' => 'approved',
            'legal_reviewed_at' => now()->toDateString(),
            'legal_tables_validation_status' => 'completed',
            'legal_tables_validation_completed_at' => now()->toDateString(),
            'legal_tables_validation_notes' => 'Validacao técnica concluída.',
            'legal_tables_review_status' => 'approved',
            'legal_tables_reviewed_at' => now()->toDateString(),
            'legal_tables_review_notes' => 'Validação externa concluída.',
            'fiscal_calendar_validation_status' => 'completed',
            'fiscal_calendar_validation_completed_at' => now()->toDateString(),
            'fiscal_calendar_validation_notes' => 'Validated current and next year fiscal obligations.',
            'fiscal_calendar_export_status' => 'generated',
            'fiscal_calendar_export_generated_at' => now()->toDateString(),
            'fiscal_calendar_export_year' => now()->year,
            'fiscal_calendar_export_file_name' => sprintf('mozambique-fiscal-calendar-%d.csv', now()->year),
            'fiscal_calendar_export_notes' => 'CSV export generated for annual fiscal review.',
            'commercial_readiness_status' => 'approved',
            'commercial_reviewed_at' => now()->toDateString(),
            'pilot_status' => 'completed',
            'pilot_completed_at' => now()->toDateString(),
            'pilot_company_count' => 2,
            'payroll_sector_validation_status' => 'completed',
            'payroll_sector_validation_completed_at' => now()->toDateString(),
            'accounting_local_validation_status' => 'completed',
            'accounting_local_validation_completed_at' => now()->toDateString(),
            'e2e_sales_flow_status' => 'completed',
            'e2e_purchase_flow_status' => 'completed',
            'e2e_pos_flow_status' => 'completed',
            'e2e_payroll_flow_status' => 'completed',
            'e2e_completed_at' => now()->toDateString(),
            'backup_restore_status' => 'completed',
            'backup_restore_tested_at' => now()->toDateString(),
            'backup_restore_evidence_ref' => 'backup_indicoerp_20260603.manifest',
            'backup_restore_notes' => 'Restore testado em base temporaria antes do go-live.',
            'go_live_approved' => 'on',
            'go_live_approved_at' => now()->toDateString(),
        ];

        $response = $this->actingAs($company)
            ->post(route('account.reports.mozambique-go-live-readiness.attestation'), $payload);

        $response->assertOk()
            ->assertJsonPath('data.attestations.legal_review_status', 'approved')
            ->assertJsonPath('data.attestations.legal_tables_validation_status', 'completed')
            ->assertJsonPath('data.attestations.legal_tables_review_status', 'approved')
            ->assertJsonPath('data.attestations.fiscal_calendar_validation_status', 'completed')
            ->assertJsonPath('data.attestations.pilot_company_count', 2)
            ->assertJsonPath('data.attestations.payroll_sector_validation_status', 'completed')
            ->assertJsonPath('data.attestations.fiscal_calendar_export_status', 'generated')
            ->assertJsonPath('data.formal_go_live_criteria.payroll_sector_validation_completed', true)
            ->assertJsonPath('data.formal_go_live_criteria.legal_tables_validation_completed', true)
            ->assertJsonPath('data.formal_go_live_criteria.legal_tables_review_approved', true)
            ->assertJsonPath('data.formal_go_live_criteria.fiscal_calendar_validation_completed', true)
            ->assertJsonPath('data.formal_go_live_criteria.accounting_local_validation_completed', true)
            ->assertJsonPath('data.attestations.e2e_sales_flow_status', 'completed')
            ->assertJsonPath('data.formal_go_live_criteria.e2e_scenarios_completed', true)
            ->assertJsonPath('data.attestations.backup_restore_status', 'completed')
            ->assertJsonPath('data.formal_go_live_criteria.backup_restore_verified', true)
            ->assertJsonPath('data.attestations.go_live_approved', 'on');

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'mz_go_live_formal_approval',
            'value' => 'on',
        ]);

        $this->assertTrue(
            Setting::where('created_by', $company->id)
                ->where('key', 'mz_go_live_pilot_company_count')
                ->where('value', '2')
                ->exists()
        );

        $this->assertTrue(
            Setting::where('created_by', $company->id)
                ->where('key', 'mz_go_live_e2e_payroll_flow_status')
                ->where('value', 'completed')
                ->exists()
        );

        $this->assertTrue(
            Setting::where('created_by', $company->id)
                ->where('key', 'mz_go_live_accounting_local_validation_status')
                ->where('value', 'completed')
                ->exists()
        );

        $this->assertTrue(
            Setting::where('created_by', $company->id)
                ->where('key', 'mz_go_live_backup_restore_evidence_ref')
                ->where('value', 'backup_indicoerp_20260603.manifest')
            ->exists()
        );

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'mz_legal_tables_validation_status',
            'value' => 'completed',
        ]);

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'mz_legal_tables_review_status',
            'value' => 'approved',
        ]);

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'mz_fiscal_calendar_validation_status',
            'value' => 'completed',
        ]);

        $this->assertDatabaseHas('settings', [
            'created_by' => $company->id,
            'key' => 'mz_fiscal_calendar_export_status',
            'value' => 'generated',
        ]);
    }

    public function test_go_live_readiness_reports_legal_tables_validation_and_review_when_settings_are_recorded(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->artisan('sce:setup', [
            '--company' => $company->id,
            '--year' => 2026,
            '--skip-catalog' => true,
            '--skip-import' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $today = now()->toDateString();
        $this->setCompanySetting($company->id, 'mz_legal_tables_validation_status', 'completed');
        $this->setCompanySetting($company->id, 'mz_legal_tables_validation_completed_at', $today);
        $this->setCompanySetting($company->id, 'mz_legal_tables_validation_notes', 'Validated against official seeded tables.');
        $this->setCompanySetting($company->id, 'mz_legal_tables_review_status', 'approved');
        $this->setCompanySetting($company->id, 'mz_legal_tables_reviewed_at', $today);
        $this->setCompanySetting($company->id, 'mz_legal_tables_review_notes', 'External review approved with legal/fiscal/contabilistic sign-off.');

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertOk()
            ->assertJsonPath('formal_go_live_criteria.legal_tables_validation_completed', true)
            ->assertJsonPath('formal_go_live_criteria.legal_tables_review_approved', true)
            ->assertJsonPath('attestations.legal_tables_validation_status', 'completed')
            ->assertJsonPath('attestations.legal_tables_review_status', 'approved');

        $checks = collect($response->json('checks'));
        $this->assertNotNull($checks->firstWhere('code', 'legal.tables.gifim_configuration'));
        $this->assertNotNull($checks->firstWhere('code', 'legal.tables.electronic_money_configuration'));
        $this->assertNotNull($checks->firstWhere('code', 'legal.tables.validation.execution'));
        $this->assertNotNull($checks->firstWhere('code', 'legal.tables.external_review'));
    }

    public function test_go_live_readiness_reports_fiscal_calendar_validation_and_export_when_settings_are_recorded(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $this->artisan('sce:setup', [
            '--company' => $company->id,
            '--year' => 2026,
            '--skip-catalog' => true,
            '--skip-import' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $today = now()->toDateString();
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_validation_status', 'completed');
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_validation_completed_at', $today);
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_validation_notes', 'Validated current and next year fiscal obligations.');
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_export_status', 'generated');
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_export_generated_at', $today);
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_export_year', (string) now()->year);
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_export_file_name', sprintf('mozambique-fiscal-calendar-%d.csv', now()->year));
        $this->setCompanySetting($company->id, 'mz_fiscal_calendar_export_notes', 'CSV export generated for annual fiscal review.');

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertOk()
            ->assertJsonPath('formal_go_live_criteria.fiscal_calendar_validation_completed', true)
            ->assertJsonPath('attestations.fiscal_calendar_validation_status', 'completed')
            ->assertJsonPath('attestations.fiscal_calendar_export_status', 'generated')
            ->assertJsonPath('attestations.fiscal_calendar_export_file_name', sprintf('mozambique-fiscal-calendar-%d.csv', now()->year));

        $checks = collect($response->json('checks'));
        $this->assertNotNull($checks->firstWhere('code', 'fiscal.calendar.profile'));
        $this->assertNotNull($checks->firstWhere('code', 'fiscal.calendar.coverage.' . now()->year));
        $this->assertNotNull($checks->firstWhere('code', 'fiscal.calendar.coverage.' . (now()->year + 1)));
        $this->assertNotNull($checks->firstWhere('code', 'fiscal.calendar.routes'));
    }

    public function test_go_live_readiness_reports_saft_manual_submission_confirmation_when_history_is_recorded(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $customer = User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Fiscal Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        FiscalExportHistory::query()->create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'generated_by' => $company->id,
            'file_name' => 'mozambique-saft-manual.xml',
            'file_hash' => hash('sha256', 'mozambique-saft-manual.xml'),
            'file_path' => 'fiscal-exports/' . $company->id . '/saft_xml/mozambique-saft-manual.xml',
            'status' => 'submitted',
            'submission_channel' => 'manual_upload',
            'submission_reference' => 'AT-SAFT-2026-0001',
            'submitted_at' => now(),
            'metadata' => [
                'submission_notes' => 'Manual SAF-T submission validated with receipt reference.',
            ],
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertOk()
            ->assertJsonPath('formal_go_live_criteria.saft_submission_validation_completed', true);

        $checks = collect($response->json('checks'));
        $check = $checks->firstWhere('code', 'exports.saft_manual_submission');

        $this->assertNotNull($check);
        $this->assertSame('pass', $check['status']);
        $this->assertTrue((bool) data_get($check, 'meta.validated'));
        $this->assertSame('AT-SAFT-2026-0001', data_get($check, 'meta.latest_export.submission_reference'));
    }

    public function test_go_live_readiness_does_not_accept_previous_year_saft_submission_for_current_year_activity(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $customer = User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Fiscal Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        SalesInvoice::create([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 16,
            'discount_amount' => 0,
            'total_amount' => 116,
            'paid_amount' => 0,
            'balance_amount' => 116,
            'status' => 'posted',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        FiscalExportHistory::query()->create([
            'company_id' => $company->id,
            'export_type' => 'saft_xml',
            'period_start' => now()->subYear()->startOfMonth(),
            'period_end' => now()->subYear()->endOfMonth(),
            'generated_by' => $company->id,
            'file_name' => 'mozambique-saft-previous-year.xml',
            'file_hash' => hash('sha256', 'mozambique-saft-previous-year.xml'),
            'file_path' => 'fiscal-exports/' . $company->id . '/saft_xml/mozambique-saft-previous-year.xml',
            'status' => 'submitted',
            'submission_channel' => 'manual_upload',
            'submission_reference' => 'AT-SAFT-2025-0001',
            'submitted_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertOk()
            ->assertJsonPath('formal_go_live_criteria.saft_submission_validation_completed', false);

        $check = collect($response->json('checks'))->firstWhere('code', 'exports.saft_manual_submission');

        $this->assertNotNull($check);
        $this->assertSame('warn', $check['status']);
        $this->assertFalse((bool) data_get($check, 'meta.validated'));
        $this->assertNull(data_get($check, 'meta.latest_export.submission_reference'));
    }

    public function test_go_live_readiness_fails_when_saft_xsd_is_required_but_missing(): void
    {
        config([
            'sce.saft.require_xsd_validation' => true,
            'sce.saft.xsd_path' => '',
        ]);

        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness'));

        $response->assertOk();

        $check = collect($response->json('checks'))
            ->firstWhere('code', 'exports.saft_xsd_validation_config');

        $this->assertNotNull($check);
        $this->assertSame('fail', $check['status']);
        $this->assertTrue($check['critical']);
        $this->assertFalse($response->json('formal_go_live_criteria.critical_checks_passed'));
    }

    public function test_pilot_company_registry_crud_requires_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness.pilot-companies.index'));
        $response->assertForbidden();
    }

    public function test_pilot_company_registry_crud_flow_works(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $storeResponse = $this->actingAs($company)
            ->post(route('account.reports.mozambique-go-live-readiness.pilot-companies.store'), [
                'company_name' => 'Empresa Piloto 1',
                'company_nuit' => '400123456',
                'industry_sector' => 'Comercio',
                'contact_name' => 'Ana',
                'contact_email' => 'ana@example.com',
                'status' => 'completed',
                'pilot_start_date' => now()->toDateString(),
                'pilot_end_date' => now()->addDays(7)->toDateString(),
                'validation_result' => 'passed',
                'validation_signed_at' => now()->addDays(7)->toDateString(),
                'validation_evidence_ref' => 'PILOT-UAT-001',
            ]);

        if ($storeResponse->status() === 422) {
            $storeResponse->assertJsonStructure(['message']);
            $this->markTestSkipped('Pilot companies table is not available in this test environment.');
        }

        $storeResponse->assertOk();
        $pilotId = (int) $storeResponse->json('data.id');
        $this->assertGreaterThan(0, $pilotId);

        $listResponse = $this->actingAs($company)
            ->get(route('account.reports.mozambique-go-live-readiness.pilot-companies.index'));
        $listResponse->assertOk()->assertJsonPath('data.0.company_name', 'Empresa Piloto 1');

        $updateResponse = $this->actingAs($company)
            ->put(route('account.reports.mozambique-go-live-readiness.pilot-companies.update', $pilotId), [
                'company_name' => 'Empresa Piloto 1',
                'company_nuit' => '400123456',
                'industry_sector' => 'Comercio',
                'contact_name' => 'Ana',
                'contact_email' => 'ana@example.com',
                'status' => 'completed',
                'pilot_start_date' => now()->subDays(10)->toDateString(),
                'pilot_end_date' => now()->toDateString(),
                'validation_result' => 'passed',
                'validation_signed_at' => now()->toDateString(),
                'validation_evidence_ref' => 'PILOT-UAT-002',
            ]);

        $updateResponse->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('mz_pilot_companies', [
            'id' => $pilotId,
            'created_by' => $company->id,
            'status' => 'completed',
            'validation_result' => 'passed',
            'validation_evidence_ref' => 'PILOT-UAT-002',
        ]);

        $readinessResponse = $this->actingAs($company)
            ->get(route('account.reports.mozambique-go-live-readiness'));
        $readinessResponse->assertOk()
            ->assertJsonPath('formal_go_live_criteria.pilot_real_companies_validated', true);

        $deleteResponse = $this->actingAs($company)
            ->delete(route('account.reports.mozambique-go-live-readiness.pilot-companies.destroy', $pilotId));
        $deleteResponse->assertOk();

        $this->assertDatabaseMissing('mz_pilot_companies', [
            'id' => $pilotId,
        ]);
    }

    public function test_validation_cases_registry_crud_requires_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-go-live-readiness.validation-cases.index'));
        $response->assertForbidden();
    }

    public function test_validation_cases_registry_crud_flow_works(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);

        $storePayroll = $this->actingAs($company)
            ->post(route('account.reports.mozambique-go-live-readiness.validation-cases.store'), [
                'domain' => 'payroll',
                'company_name' => 'Empresa X',
                'company_nuit' => '400200300',
                'industry_sector' => 'Comercio',
                'scenario_code' => 'PAY-001',
                'result' => 'passed',
                'executed_at' => now()->toDateString(),
                'evidence_ref' => 'EVID-PAY-001',
            ]);

        if ($storePayroll->status() === 422) {
            $storePayroll->assertJsonStructure(['message']);
            $this->markTestSkipped('Pilot validation table is not available in this test environment.');
        }

        $storePayroll->assertOk();

        $storeAccounting = $this->actingAs($company)
            ->post(route('account.reports.mozambique-go-live-readiness.validation-cases.store'), [
                'domain' => 'accounting',
                'company_name' => 'Empresa Y',
                'company_nuit' => '400200301',
                'industry_sector' => 'Servicos',
                'scenario_code' => 'ACC-001',
                'result' => 'passed',
                'executed_at' => now()->toDateString(),
                'evidence_ref' => 'EVID-ACC-001',
            ]);
        $storeAccounting->assertOk();

        $listResponse = $this->actingAs($company)
            ->get(route('account.reports.mozambique-go-live-readiness.validation-cases.index'));
        $listResponse->assertOk();

        $accountingId = (int) $storeAccounting->json('data.id');
        $this->assertGreaterThan(0, $accountingId);

        $updateResponse = $this->actingAs($company)
            ->put(route('account.reports.mozambique-go-live-readiness.validation-cases.update', $accountingId), [
                'domain' => 'accounting',
                'company_name' => 'Empresa Y',
                'company_nuit' => '400200301',
                'industry_sector' => 'Servicos',
                'scenario_code' => 'ACC-001',
                'result' => 'passed',
                'executed_at' => now()->toDateString(),
                'evidence_ref' => 'EVID-ACC-002',
            ]);
        $updateResponse->assertOk()->assertJsonPath('data.evidence_ref', 'EVID-ACC-002');

        $readinessResponse = $this->actingAs($company)
            ->get(route('account.reports.mozambique-go-live-readiness'));
        $readinessResponse->assertOk()
            ->assertJsonPath('formal_go_live_criteria.payroll_real_cases_validated', true)
            ->assertJsonPath('formal_go_live_criteria.accounting_real_cases_validated', true);

        $deleteResponse = $this->actingAs($company)
            ->delete(route('account.reports.mozambique-go-live-readiness.validation-cases.destroy', $accountingId));
        $deleteResponse->assertOk();
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'creator_id' => null,
            'email_verified_at' => now(),
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);
    }

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'general',
                    'module' => 'tests',
                    'label' => $permissionName,
                ]
            );

            if (!$user->hasPermissionTo($permission)) {
                $user->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->refresh();
    }

    private function setCompanySetting(int $companyId, string $key, string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key, 'created_by' => $companyId],
            ['value' => $value, 'is_public' => false]
        );

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');
        Cache::forget('company_settings_owner:' . $companyId);
    }
}
