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

class HrmPayrollCancellationControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_payroll_cancellation_requires_reason(): void
    {
        [$company, $payroll] = $this->makePayrollScenario();
        $this->grantPermissions($company, ['delete-payrolls', 'manage-any-payrolls']);

        $response = $this->actingAs($company)->delete(route('hrm.payrolls.destroy', $payroll->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'completed',
        ]);
    }

    public function test_payroll_delete_route_soft_cancels_payroll_and_entries(): void
    {
        [$company, $payroll, $entry] = $this->makePayrollScenario();
        $this->grantPermissions($company, ['delete-payrolls', 'manage-any-payrolls']);

        $response = $this->actingAs($company)->delete(route('hrm.payrolls.destroy', $payroll->id), [
            'cancellation_reason' => 'Erro no processamento da folha e necessidade de nova execução.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Erro no processamento da folha e necessidade de nova execução.',
        ]);

        $this->assertDatabaseHas('payroll_entries', [
            'id' => $entry->id,
            'is_cancelled' => 1,
            'cancellation_reason' => 'Erro no processamento da folha e necessidade de nova execução.',
        ]);
    }

    public function test_payslip_cancellation_requires_reason_and_recalculates_totals(): void
    {
        [$company, $payroll, $entry, $secondEntry] = $this->makePayrollScenario(withSecondEntry: true);
        $this->grantPermissions($company, ['delete-payslip', 'manage-any-payrolls']);

        $missingReasonResponse = $this->actingAs($company)->delete(route('hrm.payroll-entries.destroy', $entry->id));
        $missingReasonResponse->assertRedirect();
        $missingReasonResponse->assertSessionHasErrors('cancellation_reason');

        $response = $this->actingAs($company)->delete(route('hrm.payroll-entries.destroy', $entry->id), [
            'cancellation_reason' => 'Entrada duplicada na folha deste período.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payroll_entries', [
            'id' => $entry->id,
            'is_cancelled' => 1,
            'cancellation_reason' => 'Entrada duplicada na folha deste período.',
        ]);

        $payroll->refresh();
        $secondEntry->refresh();

        $this->assertSame((float) $secondEntry->gross_pay, (float) $payroll->total_gross_pay);
        $this->assertSame((float) $secondEntry->total_deductions, (float) $payroll->total_deductions);
        $this->assertSame((float) $secondEntry->net_pay, (float) $payroll->total_net_pay);
        $this->assertSame(1, (int) $payroll->employee_count);
    }

    private function makePayrollScenario(bool $withSecondEntry = false): array
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario A');

        Employee::query()->create([
            'employee_id' => 'EMP-CAN-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400998877',
            'basic_salary' => 20000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Controle Cancelamento',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => now()->startOfMonth()->toDateString(),
            'pay_period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->endOfMonth()->toDateString(),
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_gross_pay' => 23000,
            'total_deductions' => 1800,
            'total_net_pay' => 21200,
            'total_irps' => 600,
            'total_inss_employee' => 600,
            'total_inss_employer' => 800,
            'employee_count' => $withSecondEntry ? 2 : 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $firstEntry = PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 20000,
            'gross_pay' => 23000,
            'total_deductions' => 1800,
            'net_pay' => 21200,
            'irps_amount' => 600,
            'inss_employee_amount' => 600,
            'inss_employer_amount' => 800,
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        if (!$withSecondEntry) {
            return [$company, $payroll, $firstEntry];
        }

        $employeeUserB = $this->makeEmployeeUser($company, 'Funcionario B');

        Employee::query()->create([
            'employee_id' => 'EMP-CAN-002',
            'user_id' => $employeeUserB->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400112233',
            'basic_salary' => 10000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $secondEntry = PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUserB->id,
            'basic_salary' => 10000,
            'gross_pay' => 10000,
            'total_deductions' => 500,
            'net_pay' => 9500,
            'irps_amount' => 200,
            'inss_employee_amount' => 300,
            'inss_employer_amount' => 400,
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll->update([
            'total_gross_pay' => 33000,
            'total_deductions' => 2300,
            'total_net_pay' => 30700,
            'total_irps' => 800,
            'total_inss_employee' => 900,
            'total_inss_employer' => 1200,
            'employee_count' => 2,
        ]);

        return [$company, $payroll, $firstEntry, $secondEntry];
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
