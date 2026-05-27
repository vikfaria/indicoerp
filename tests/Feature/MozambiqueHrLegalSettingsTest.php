<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use App\Services\MozambiqueForeignWorkerQuotaService;
use App\Services\MozambiqueHrLegalSettingsService;
use App\Services\MozambiqueProbationPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;

class MozambiqueHrLegalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_can_update_mozambique_legal_settings_from_compliance_route(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $payload = [
            'foreign_quota' => [
                'micro_max_workers' => 12,
                'small_max_workers' => 40,
                'medium_max_workers' => 120,
                'micro_quota_percent' => 14.5,
                'small_quota_percent' => 9.5,
                'medium_quota_percent' => 7.5,
                'large_quota_percent' => 4.5,
            ],
            'probation_limits_days' => [
                'base_indefinite' => 25,
                'general' => 55,
                'technician_mid' => 85,
                'technician_high' => 170,
                'leadership' => 175,
            ],
            'probation_alert_days' => [
                'primary' => 20,
                'secondary' => 10,
            ],
        ];

        $this->actingAs($company)->put(
            route('hrm.mozambique-payroll-compliance.legal-settings.update'),
            $payload
        )->assertRedirect();

        $saved = app(MozambiqueHrLegalSettingsService::class)->getSettings($company->id);

        $this->assertSame(12, $saved['foreign_quota']['micro_max_workers']);
        $this->assertSame(40, $saved['foreign_quota']['small_max_workers']);
        $this->assertSame(120, $saved['foreign_quota']['medium_max_workers']);
        $this->assertSame(14.5, $saved['foreign_quota']['micro_quota_percent']);
        $this->assertSame(85, $saved['probation_limits_days']['technician_mid']);
        $this->assertSame(20, $saved['probation_alert_days']['primary']);
        $this->assertSame(10, $saved['probation_alert_days']['secondary']);
    }

    public function test_foreign_quota_service_uses_configured_thresholds_and_percentages(): void
    {
        $company = $this->makeCompany();

        app(MozambiqueHrLegalSettingsService::class)->updateSettings($company->id, [
            'foreign_quota' => [
                'micro_max_workers' => 20,
                'small_max_workers' => 40,
                'medium_max_workers' => 80,
                'micro_quota_percent' => 20,
                'small_quota_percent' => 12,
                'medium_quota_percent' => 9,
                'large_quota_percent' => 4,
            ],
            'probation_limits_days' => [
                'base_indefinite' => 30,
                'general' => 60,
                'technician_mid' => 90,
                'technician_high' => 180,
                'leadership' => 180,
            ],
            'probation_alert_days' => [
                'primary' => 15,
                'secondary' => 7,
            ],
        ]);

        $employees = collect();
        for ($i = 1; $i <= 10; $i++) {
            $staff = User::factory()->create([
                'type' => 'staff',
                'created_by' => $company->id,
                'creator_id' => $company->id,
            ]);

            $employees->push(Employee::query()->create([
                'employee_id' => 'EMP-Q-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'user_id' => $staff->id,
                'employment_type' => 'GENERAL',
                'basic_salary' => 10000,
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]));
        }

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employees[0]->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $evaluation = app(MozambiqueForeignWorkerQuotaService::class)->evaluate($company->id);

        $this->assertSame('micro', $evaluation['employer_type']);
        $this->assertSame(20.0, (float) $evaluation['max_percentage']);
        $this->assertSame(2, (int) $evaluation['quota_slots']);
        $this->assertSame(1, (int) $evaluation['current_foreign_workers']);
        $this->assertFalse((bool) $evaluation['is_exceeded']);
    }

    public function test_probation_policy_uses_configured_limits_and_alert_days(): void
    {
        $company = $this->makeCompany();

        app(MozambiqueHrLegalSettingsService::class)->updateSettings($company->id, [
            'foreign_quota' => [
                'micro_max_workers' => 10,
                'small_max_workers' => 30,
                'medium_max_workers' => 100,
                'micro_quota_percent' => 15,
                'small_quota_percent' => 10,
                'medium_quota_percent' => 8,
                'large_quota_percent' => 5,
            ],
            'probation_limits_days' => [
                'base_indefinite' => 20,
                'general' => 45,
                'technician_mid' => 70,
                'technician_high' => 140,
                'leadership' => 150,
            ],
            'probation_alert_days' => [
                'primary' => 12,
                'secondary' => 5,
            ],
        ]);

        $policy = app(MozambiqueProbationPolicyService::class);

        $this->assertSame(70, $policy->legalMaxDaysFor('technician_mid', $company->id));
        $this->assertSame('2026-03-12', $policy->calculateExpectedEndDate('2026-01-01', 'technician_mid', $company->id));

        $alerts = $policy->buildAlerts(now()->addDays(10)->toDateString(), $company->id);
        $this->assertTrue($alerts['alert_15_days']);
        $this->assertFalse($alerts['alert_7_days']);

        $alertsNearEnd = $policy->buildAlerts(now()->addDays(4)->toDateString(), $company->id);
        $this->assertTrue($alertsNearEnd['alert_15_days']);
        $this->assertTrue($alertsNearEnd['alert_7_days']);
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

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                [
                    'add_on' => 'hrm',
                    'module' => 'hrm',
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

