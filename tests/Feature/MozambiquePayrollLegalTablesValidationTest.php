<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use App\Services\MozambiqueLegalTablesValidationService;
use App\Services\MozambiquePayrollLegalDefaultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;

class MozambiquePayrollLegalTablesValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_official_payroll_tables_validate_as_pass_after_legal_seed_setup(): void
    {
        $company = $this->makeCompany();
        app(MozambiquePayrollLegalDefaultsService::class)->seedForCompany($company->id);

        $validation = app(MozambiqueLegalTablesValidationService::class)->validate($company->id);
        $checks = collect($validation['checks'])->keyBy('code');

        $this->assertSame('pass', $checks->get('legal.tables.irps_table')['status']);
        $this->assertSame('pass', $checks->get('legal.tables.inss_rate')['status']);
        $this->assertSame('pass', $checks->get('legal.tables.minimum_wages')['status']);
    }

    public function test_run_payroll_blocks_when_legal_irps_table_no_longer_matches_official_brackets(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['run-payrolls', 'manage-any-payrolls']);
        app(MozambiquePayrollLegalDefaultsService::class)->seedForCompany($company->id);
        $this->setWorkingDays($company->id);

        $employeeUser = $this->makeEmployeeUser($company, 'Payroll IRPS Check');
        Employee::query()->create([
            'employee_id' => 'EMP-IRPS-GATE-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 20000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = $this->makePayroll($company, 'Payroll IRPS Gate', '2026-06-01', '2026-06-30');

        $irpsBracket = \App\Models\MozIrpsBracket::query()
            ->whereHas('irpsTable', function ($query) use ($company): void {
                $query->where('created_by', $company->id);
            })
            ->where('sequence', 1)
            ->firstOrFail();

        $irpsBracket->update(['rate_percent' => 11]);

        $response = $this->actingAs($company)->post(route('hrm.payrolls.run', $payroll));

        $response->assertRedirect();
        $this->assertSame('draft', $payroll->fresh()->status);
        $this->assertStringContainsString(
            'Official payroll tables do not match',
            (string) session('error')
        );
    }

    public function test_run_payroll_blocks_when_employee_salary_is_below_legal_minimum_wage(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['run-payrolls', 'manage-any-payrolls']);
        app(MozambiquePayrollLegalDefaultsService::class)->seedForCompany($company->id);
        $this->setWorkingDays($company->id);

        $employeeUser = $this->makeEmployeeUser($company, 'Payroll Minimum Wage Check');
        Employee::query()->create([
            'employee_id' => 'EMP-MW-GATE-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'S7_SERVICOS_NAO_FINANCEIROS',
            'basic_salary' => 9000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = $this->makePayroll($company, 'Payroll Minimum Wage Gate', '2026-06-01', '2026-06-30');

        $response = $this->actingAs($company)->post(route('hrm.payrolls.run', $payroll));

        $response->assertRedirect();
        $this->assertSame('draft', $payroll->fresh()->status);
        $this->assertStringContainsString(
            'Minimum wage non-compliance found for employees',
            (string) session('error')
        );
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

    private function makeEmployeeUser(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makePayroll(User $company, string $title, string $startDate, string $endDate): Payroll
    {
        return Payroll::query()->create([
            'title' => $title,
            'payroll_frequency' => 'monthly',
            'pay_period_start' => $startDate,
            'pay_period_end' => $endDate,
            'pay_date' => $endDate,
            'status' => 'draft',
            'is_payroll_paid' => 'unpaid',
            'employee_count' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function setWorkingDays(int $companyId): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'working_days', 'created_by' => $companyId],
            ['value' => json_encode([1, 2, 3, 4, 5], JSON_THROW_ON_ERROR), 'is_public' => false]
        );

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');
        Cache::forget('company_settings_owner:' . $companyId);
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
