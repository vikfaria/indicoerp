<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AccountingJournal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Services\JournalService;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class HrmPayrollAccountingApiExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_api_exports_return_cost_journal_and_monthly_summary_datasets(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->createPayrollJournalForCompany($companyA, 'API USER A', '2026-05-31', true);
        $this->createPayrollJournalForCompany($companyB, 'API USER B', '2026-05-31', false);

        $this->grantPermissions($companyA, ['view-payrolls']);

        $monthlyResponse = $this->actingAs($companyA, 'sanctum')
            ->getJson('/api/hrm/payroll-accounting/monthly-summary?reference_period=2026-05');

        $monthlyResponse->assertOk();
        $monthlyResponse->assertJsonPath('success', true);
        $monthlyResponse->assertJsonPath('data.reference_period', '2026-05');
        $monthlyResponse->assertJsonPath('data.summary.payroll_runs', 1);
        $monthlyResponse->assertJsonPath('data.rows.0.payroll_title', 'Payroll Export');

        $costResponse = $this->actingAs($companyA, 'sanctum')
            ->getJson('/api/hrm/payroll-accounting/cost-allocation?reference_period=2026-05');

        $costResponse->assertOk();
        $costResponse->assertJsonPath('success', true);
        $costResponse->assertJsonPath('data.reference_period', '2026-05');
        $costResponse->assertJsonPath('data.summary.rows', 1);

        $journalResponse = $this->actingAs($companyA, 'sanctum')
            ->getJson('/api/hrm/payroll-accounting/journal-lines?reference_period=2026-05');

        $journalResponse->assertOk();
        $journalResponse->assertJsonPath('success', true);
        $journalResponse->assertJsonPath('data.reference_period', '2026-05');
        $this->assertSame('API USER A', data_get($journalResponse->json(), 'data.rows.0.employee_name'));
        $this->assertStringNotContainsString('API USER B', json_encode($journalResponse->json(), JSON_THROW_ON_ERROR));
    }

    public function test_api_exports_require_view_payroll_permission(): void
    {
        $company = $this->makeCompany();
        $this->createPayrollJournalForCompany($company, 'API USER NO PERM', '2026-05-31', true);

        $response = $this->actingAs($company, 'sanctum')
            ->getJson('/api/hrm/payroll-accounting/monthly-summary?reference_period=2026-05');

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    private function createPayrollJournalForCompany(User $company, string $employeeName, string $payDate, bool $seedSalaryJournal): void
    {
        $employeeUser = $this->makeEmployeeUser($company, $employeeName);

        AccountUtility::defaultdata($company->id);
        if ($seedSalaryJournal) {
            AccountingJournal::seedDefaults($company->id);
        }

        Employee::query()->create([
            'employee_id' => 'EMP-' . $company->id . '-' . $employeeUser->id,
            'user_id' => $employeeUser->id,
            'basic_salary' => 25000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $bankGlAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        $bankAccount = BankAccount::query()->create([
            'account_number' => 'ACC-' . $company->id,
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
            'title' => 'Payroll Export',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => $payDate,
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_gross_pay' => 26000,
            'total_deductions' => 1800,
            'total_net_pay' => 24200,
            'total_irps' => 800,
            'total_inss_employee' => 750,
            'total_inss_employer' => 1000,
            'employee_count' => 1,
            'bank_account_id' => $bankAccount->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 25000,
            'gross_pay' => 26000,
            'net_pay' => 24200,
            'taxable_income' => 26000,
            'irps_amount' => 800,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 750,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1000,
            'statutory_deductions_total' => 1550,
            'total_allowances' => 1000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1800,
            'total_loans' => 0,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);
        app(JournalService::class)->createPayrollJournal($entry);
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
