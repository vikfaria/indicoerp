<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class HrmPayrollMapExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_payroll_mozambique_map_export_returns_csv_for_same_tenant(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario MZ');

        Employee::create([
            'employee_id' => 'EMP-0001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::create([
            'title' => 'Payroll Abril 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-04-01',
            'pay_period_end' => '2026-04-30',
            'pay_date' => '2026-04-30',
            'status' => 'completed',
            'total_gross_pay' => 32000,
            'total_deductions' => 2200,
            'total_net_pay' => 29800,
            'total_irps' => 1200,
            'total_inss_employee' => 960,
            'total_inss_employer' => 1280,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 2200,
            'total_loans' => 0,
            'taxable_income' => 32000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2160,
            'gross_pay' => 32000,
            'net_pay' => 29800,
            'working_days' => 22,
            'minimum_wage_required' => 9000,
            'minimum_wage_compliant' => true,
            'minimum_wage_gap' => 0,
            'payroll_sector_code' => 'GENERAL',
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls', 'manage-any-payrolls']);

        $response = $this->actingAs($company)->get(route('hrm.payrolls.mozambique-map', $payroll));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertSee('Payroll ID', false);
        $response->assertSee('IRPS', false);
        $response->assertSee('INSS Employee Amount', false);
        $response->assertSee('Funcionario MZ', false);
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
