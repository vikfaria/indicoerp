<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\AccountingJournal;
use App\Models\CostCenter;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Services\JournalService;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class HrmPayrollAccountingExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_cost_allocation_export_returns_department_allocation_with_cost_center(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Centro Custo');

        $branch = Branch::query()->create([
            'branch_name' => 'Maputo',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $department = Department::query()->create([
            'department_name' => 'Contabilidade',
            'branch_id' => $branch->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $designation = Designation::query()->create([
            'designation_name' => 'Contabilista',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-CCA-001',
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'tax_payer_id' => '400778899',
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        CostCenter::query()->create([
            'company_id' => $company->id,
            'code' => 'DEP-' . $department->id,
            'name' => 'Contabilidade',
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Maio 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => '2026-05-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 32000,
            'total_deductions' => 2500,
            'total_net_pay' => 29500,
            'total_irps' => 1200,
            'total_inss_employee' => 960,
            'total_inss_employer' => 1280,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'gross_pay' => 32000,
            'net_pay' => 29500,
            'taxable_income' => 32000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2160,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 2500,
            'total_loans' => 0,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.cost-allocation.export',
            ['reference_period' => '2026-05']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Cost Center Code', false);
        $response->assertSee('DEP-' . $department->id, false);
        $response->assertSee('Contabilidade', false);
        $response->assertSee('Funcionario Centro Custo', false);
    }

    public function test_accounting_journal_export_is_scoped_to_company(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $journalA = $this->createPayrollJournalForCompany($companyA, 'FUNC A', '2026-05-31', true);
        $this->createPayrollJournalForCompany($companyB, 'FUNC OUTSIDE', '2026-05-31', false);

        $this->grantPermissions($companyA, ['view-payrolls']);

        $response = $this->actingAs($companyA)->get(route(
            'hrm.mozambique-payroll-compliance.reports.accounting-journal-lines.export',
            ['reference_period' => '2026-05']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Journal Number', false);
        $response->assertSee('Account Code', false);
        $response->assertSee($journalA->journal_number, false);
        $response->assertSee('FUNC A', false);
        $response->assertDontSee('FUNC OUTSIDE', false);
    }

    public function test_cost_center_mapping_configuration_updates_and_drives_strict_allocation(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Mapeado');

        $branch = Branch::query()->create([
            'branch_name' => 'Maputo',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $department = Department::query()->create([
            'department_name' => 'Tesouraria',
            'branch_id' => $branch->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $designation = Designation::query()->create([
            'designation_name' => 'Analista',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-MAP-001',
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'tax_payer_id' => '400112233',
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $costCenter = CostCenter::query()->create([
            'company_id' => $company->id,
            'code' => 'CC-STRICT-01',
            'name' => 'Centro Tesouraria',
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Maio 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => '2026-05-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 32000,
            'total_deductions' => 2500,
            'total_net_pay' => 29500,
            'total_irps' => 1200,
            'total_inss_employee' => 960,
            'total_inss_employer' => 1280,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 30000,
            'gross_pay' => 32000,
            'net_pay' => 29500,
            'taxable_income' => 32000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2160,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 2500,
            'total_loans' => 0,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['edit-payrolls', 'view-payrolls']);

        $this->actingAs($company)->put(
            route('hrm.mozambique-payroll-compliance.cost-center-mappings.update'),
            [
                'mode' => 'configured',
                'mappings' => [
                    'employee' => [],
                    'department' => [
                        (string) $department->id => $costCenter->id,
                    ],
                    'branch' => [],
                ],
            ]
        )->assertRedirect();

        $modeSetting = Setting::query()
            ->where('created_by', $company->id)
            ->where('key', 'mz_payroll_cost_center_mapping_mode')
            ->first();
        $this->assertNotNull($modeSetting);
        $this->assertSame('configured', $modeSetting->value);

        $rulesSetting = Setting::query()
            ->where('created_by', $company->id)
            ->where('key', 'mz_payroll_cost_center_mapping_rules')
            ->first();
        $this->assertNotNull($rulesSetting);

        $rules = json_decode((string) $rulesSetting->value, true);
        $this->assertSame($costCenter->id, $rules['department'][(string) $department->id] ?? null);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.cost-allocation.export',
            ['reference_period' => '2026-05']
        ));

        $response->assertOk();
        $response->assertSee('CC-STRICT-01', false);
        $response->assertSee('Centro Tesouraria', false);
    }

    public function test_json_and_xml_exports_return_structured_payloads(): void
    {
        $company = $this->makeCompany();
        $this->createPayrollJournalForCompany($company, 'FUNC JSON XML', '2026-05-31', true);
        $this->grantPermissions($company, ['view-payrolls']);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.accounting-journal-lines.json',
            ['reference_period' => '2026-05']
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'reference_period',
            'period_start',
            'period_end',
            'summary',
            'rows',
        ]);
        $jsonResponse->assertJsonPath('reference_period', '2026-05');
        $jsonResponse->assertSee('FUNC JSON XML', false);

        $xmlResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.cost-allocation.xml',
            ['reference_period' => '2026-05']
        ));

        $xmlResponse->assertOk();
        $xmlResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('<payroll_cost_allocation>', (string) $xmlResponse->getContent());
        $this->assertStringContainsString('<reference_period>2026-05</reference_period>', (string) $xmlResponse->getContent());
    }

    public function test_monthly_summary_exports_cover_csv_json_xml_and_xlsx_formats(): void
    {
        $company = $this->makeCompany();
        $this->createPayrollJournalForCompany($company, 'FUNC SUMMARY', '2026-05-31', true);
        $this->grantPermissions($company, ['view-payrolls']);

        $csvResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.payroll-monthly-summary.export',
            ['reference_period' => '2026-05']
        ));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csvResponse->assertSee('Payroll Title', false);
        $csvResponse->assertSee('Payroll Export', false);
        $csvResponse->assertSee('Gross Pay Total', false);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.payroll-monthly-summary.json',
            ['reference_period' => '2026-05']
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'reference_period',
            'period_start',
            'period_end',
            'summary',
            'rows',
        ]);
        $jsonResponse->assertJsonPath('reference_period', '2026-05');
        $jsonResponse->assertJsonPath('summary.payroll_runs', 1);
        $jsonResponse->assertJsonPath('rows.0.payroll_title', 'Payroll Export');

        $xmlResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.payroll-monthly-summary.xml',
            ['reference_period' => '2026-05']
        ));

        $xmlResponse->assertOk();
        $xmlResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('<payroll_monthly_summary>', (string) $xmlResponse->getContent());
        $this->assertStringContainsString('<reference_period>2026-05</reference_period>', (string) $xmlResponse->getContent());

        $xlsxResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.payroll-monthly-summary.xlsx',
            ['reference_period' => '2026-05']
        ));

        $xlsxResponse->assertOk();
        $xlsxResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', (string) $xlsxResponse->getContent());
    }

    public function test_cost_and_journal_xlsx_exports_are_available(): void
    {
        $company = $this->makeCompany();
        $this->createPayrollJournalForCompany($company, 'FUNC XLSX', '2026-05-31', true);
        $this->grantPermissions($company, ['view-payrolls']);

        $costResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.cost-allocation.xlsx',
            ['reference_period' => '2026-05']
        ));
        $costResponse->assertOk();
        $costResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', (string) $costResponse->getContent());

        $journalResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.accounting-journal-lines.xlsx',
            ['reference_period' => '2026-05']
        ));
        $journalResponse->assertOk();
        $journalResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', (string) $journalResponse->getContent());
    }

    private function createPayrollJournalForCompany(User $company, string $employeeName, string $payDate, bool $seedSalaryJournal): \Workdo\Account\Models\JournalEntry
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
        return app(JournalService::class)->createPayrollJournal($entry);
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
