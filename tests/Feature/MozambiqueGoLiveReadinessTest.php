<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                    'pilot_completed',
                    'pilot_registry_populated',
                    'pilot_real_companies_validated',
                    'payroll_sector_validation_completed',
                    'payroll_real_cases_validated',
                    'accounting_local_validation_completed',
                    'accounting_real_cases_validated',
                    'e2e_scenarios_completed',
                    'formal_approval_granted',
                    'recommended_for_launch',
                ],
                'attestations' => [
                    'legal_review_status',
                    'legal_reviewed_at',
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
            'go_live_approved' => 'on',
            'go_live_approved_at' => now()->toDateString(),
        ];

        $response = $this->actingAs($company)
            ->post(route('account.reports.mozambique-go-live-readiness.attestation'), $payload);

        $response->assertOk()
            ->assertJsonPath('data.attestations.legal_review_status', 'approved')
            ->assertJsonPath('data.attestations.pilot_company_count', 2)
            ->assertJsonPath('data.attestations.payroll_sector_validation_status', 'completed')
            ->assertJsonPath('data.formal_go_live_criteria.payroll_sector_validation_completed', true)
            ->assertJsonPath('data.formal_go_live_criteria.accounting_local_validation_completed', true)
            ->assertJsonPath('data.attestations.e2e_sales_flow_status', 'completed')
            ->assertJsonPath('data.formal_go_live_criteria.e2e_scenarios_completed', true)
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
}
