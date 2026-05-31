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

class HrmPayrollEntryIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_payslip_print_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['download-payslip', 'manage-any-payrolls']);

        [, , $entryB] = $this->makePayrollScenario($companyB, 'B');

        $response = $this->actingAs($companyA)->get(route('hrm.payroll-entries.print', $entryB->id));

        $response->assertRedirect(route('hrm.payrolls.index'));
        $response->assertSessionHas('error');
    }

    public function test_payslip_print_allows_same_company_record_access(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['download-payslip', 'manage-any-payrolls']);

        [, , $entry] = $this->makePayrollScenario($company, 'A');

        $response = $this->actingAs($company)->get(route('hrm.payroll-entries.print', $entry->id));

        $response->assertOk();
    }

    public function test_payslip_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-payslip', 'manage-any-payrolls']);

        [, , $entryB] = $this->makePayrollScenario($companyB, 'D');

        $response = $this->actingAs($companyA)->delete(route('hrm.payroll-entries.destroy', $entryB->id), [
            'cancellation_reason' => 'Cross-company cancellation attempt',
        ]);

        $response->assertRedirect(route('hrm.payrolls.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_entries', [
            'id' => $entryB->id,
            'status' => 'unpaid',
            'is_cancelled' => 0,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_payslip_pay_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['pay-payslip', 'manage-any-payrolls']);

        [, , $entryB] = $this->makePayrollScenario($companyB, 'P');

        $response = $this->actingAs($companyA)->patch(route('hrm.payroll-entries.pay', $entryB->id));

        $response->assertRedirect(route('hrm.payrolls.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_entries', [
            'id' => $entryB->id,
            'status' => 'unpaid',
            'created_by' => $companyB->id,
        ]);
    }

    private function makePayrollScenario(User $company, string $suffix): array
    {
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario ' . $suffix);

        $employee = Employee::query()->create([
            'employee_id' => 'EMP-PRT-' . $suffix,
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 20000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll ' . $suffix,
            'payroll_frequency' => 'monthly',
            'pay_period_start' => now()->startOfMonth()->toDateString(),
            'pay_period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->endOfMonth()->toDateString(),
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_gross_pay' => 22000,
            'total_deductions' => 1500,
            'total_net_pay' => 20500,
            'total_irps' => 600,
            'total_inss_employee' => 600,
            'total_inss_employer' => 800,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 20000,
            'gross_pay' => 22000,
            'total_deductions' => 1500,
            'net_pay' => 20500,
            'irps_amount' => 600,
            'inss_employee_amount' => 600,
            'inss_employer_amount' => 800,
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$employee, $payroll, $entry];
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
