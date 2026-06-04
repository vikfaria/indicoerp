<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AddOn;
use App\Classes\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;
use App\Models\UserActiveModule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HrmPayrollPaymentJournalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_paying_a_payslip_posts_journal_and_bank_transaction_once(): void
    {
        [$company, $entry] = $this->bootstrapPayrollEntry();
        $this->grantPermissions($company, ['pay-payslip', 'manage-payrolls']);

        $response = $this->actingAs($company)->patch(route('hrm.payroll-entries.pay', $entry->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $entry->refresh();
        $payroll = $entry->payroll()->firstOrFail();

        $this->assertSame('paid', $entry->status);
        $this->assertSame('paid', $payroll->is_payroll_paid);

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'payroll',
            'reference_id' => $entry->id,
            'created_by' => $company->id,
        ]);

        $this->assertDatabaseCount('bank_transactions', 1);
        $this->assertDatabaseHas('bank_transactions', [
            'reference_number' => 'PAYROLL-' . $entry->id,
            'transaction_type' => 'debit',
            'created_by' => $company->id,
        ]);

        $secondResponse = $this->actingAs($company)->patch(route('hrm.payroll-entries.pay', $entry->id));

        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHas('error', __('Paid payslips cannot be paid again.'));

        $this->assertSame(1, JournalEntry::query()->where('reference_type', 'payroll')->where('reference_id', $entry->id)->count());
        $this->assertSame(1, BankTransaction::query()->where('reference_number', 'PAYROLL-' . $entry->id)->count());
    }

    private function bootstrapPayrollEntry(): array
    {
        $company = User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'active_plan' => 1,
            'plan_expire_date' => now()->addMonth(),
        ]);

        AccountUtility::defaultdata($company->id);
        AddOn::query()->updateOrCreate(
            ['module' => 'Account'],
            [
                'name' => 'Account',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'image' => '',
                'for_admin' => false,
                'package_name' => 'account',
                'priority' => 20,
                'is_enable' => true,
            ]
        );
        AddOn::query()->updateOrCreate(
            ['module' => 'Hrm'],
            [
                'name' => 'HRM',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'image' => '',
                'for_admin' => false,
                'package_name' => 'hrm',
                'priority' => 30,
                'is_enable' => true,
            ]
        );
        app(Module::class)->moduleCacheForget('Account');
        app(Module::class)->moduleCacheForget('Hrm');
        UserActiveModule::query()->updateOrCreate(
            ['user_id' => $company->id, 'module' => 'Account'],
            ['user_id' => $company->id, 'module' => 'Account']
        );
        UserActiveModule::query()->updateOrCreate(
            ['user_id' => $company->id, 'module' => 'Hrm'],
            ['user_id' => $company->id, 'module' => 'Hrm']
        );

        $employeeUser = User::factory()->create([
            'name' => 'Funcionario Pagamento',
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-PAY-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $bankGlAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        $bankAccount = BankAccount::query()->create([
            'account_number' => '000123456789',
            'account_name' => 'Conta Salarios',
            'bank_name' => 'Banco MZ',
            'branch_name' => 'Maputo',
            'account_type' => 'current',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'gl_account_id' => $bankGlAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Junho 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-06-01',
            'pay_period_end' => '2026-06-30',
            'pay_date' => '2026-06-30',
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_gross_pay' => 32000,
            'total_deductions' => 4160,
            'total_net_pay' => 27840,
            'total_irps' => 1200,
            'total_inss_employee' => 960,
            'total_inss_employer' => 1280,
            'employee_count' => 1,
            'bank_account_id' => $bankAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'gross_pay' => 32000,
            'net_pay' => 27840,
            'taxable_income' => 32000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2160,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 4160,
            'total_loans' => 0,
            'working_days' => 22,
            'status' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$company, $entry];
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
