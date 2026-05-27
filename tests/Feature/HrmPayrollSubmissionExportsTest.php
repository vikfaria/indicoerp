<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractType;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDependent;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class HrmPayrollSubmissionExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_modelo19_export_returns_csv_with_period_payroll_data(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario IRPS');

        Employee::query()->create([
            'employee_id' => 'EMP-IRPS-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '400123456',
            'employment_type' => 'GENERAL',
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employee = Employee::query()
            ->where('user_id', $employeeUser->id)
            ->where('created_by', $company->id)
            ->firstOrFail();

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
            'nationality' => 'AO',
            'residency_status' => 'non_resident',
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeDependent::query()->create([
            'employee_id' => $employee->id,
            'full_name' => 'Dependente A',
            'relationship' => 'child',
            'date_of_birth' => '2014-05-02',
            'is_student' => true,
            'is_tax_eligible' => true,
            'valid_until' => '2030-01-01',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'mz_irps_dependent_deduction_amount', 'created_by' => $company->id],
            ['value' => '500', 'is_public' => false]
        );

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Março 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-03-01',
            'pay_period_end' => '2026-03-31',
            'pay_date' => '2026-03-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 32000,
            'total_deductions' => 2400,
            'total_net_pay' => 29600,
            'total_irps' => 1400,
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
            'taxable_income' => 32000,
            'irps_amount' => 1400,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 960,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1280,
            'statutory_deductions_total' => 2360,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 2400,
            'total_loans' => 0,
            'net_pay' => 29600,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.modelo19-support.export',
            ['reference_period' => '2026-03']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Reference Period', false);
        $response->assertSee('IRPS Withheld', false);
        $response->assertSee('Residency Status', false);
        $response->assertSee('Eligible Dependents', false);
        $response->assertSee('Adjusted Taxable Income', false);
        $response->assertSee('2026-03', false);
        $response->assertSee('Funcionario IRPS', false);
        $response->assertSee('400123456', false);
        $response->assertSee('non_resident', false);
    }

    public function test_inss_export_is_isolated_by_company(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario INSS');

        Employee::query()->create([
            'employee_id' => 'EMP-INSS-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '400987654',
            'employment_type' => 'GENERAL',
            'basic_salary' => 25000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Abril 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-04-01',
            'pay_period_end' => '2026-04-30',
            'pay_date' => '2026-04-30',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 25000,
            'total_deductions' => 1750,
            'total_net_pay' => 23250,
            'total_irps' => 0,
            'total_inss_employee' => 750,
            'total_inss_employer' => 1000,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 25000,
            'gross_pay' => 25000,
            'taxable_income' => 25000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 750,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1000,
            'statutory_deductions_total' => 1750,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1750,
            'total_loans' => 0,
            'net_pay' => 23250,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499999999',
            'employment_type' => 'GENERAL',
            'basic_salary' => 12000,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $otherPayroll = Payroll::query()->create([
            'title' => 'Payroll Abril Empresa B',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-04-01',
            'pay_period_end' => '2026-04-30',
            'pay_date' => '2026-04-30',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 12000,
            'total_deductions' => 840,
            'total_net_pay' => 11160,
            'total_irps' => 0,
            'total_inss_employee' => 360,
            'total_inss_employer' => 480,
            'employee_count' => 1,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $otherPayroll->id,
            'employee_id' => $otherEmployeeUser->id,
            'basic_salary' => 12000,
            'gross_pay' => 12000,
            'taxable_income' => 12000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 360,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 480,
            'statutory_deductions_total' => 840,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 840,
            'total_loans' => 0,
            'net_pay' => 11160,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.inss-guide.export',
            ['reference_period' => '2026-04']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Total INSS Contribution', false);
        $response->assertSee('Funcionario INSS', false);
        $response->assertDontSee('OUTSIDE EMPLOYEE', false);
    }

    public function test_submission_exports_require_view_payroll_permission(): void
    {
        $company = $this->makeCompany();

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.modelo19-support.export',
            ['reference_period' => '2026-03']
        ));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $bankResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.bank-payment-file.export',
            ['reference_period' => '2026-03']
        ));

        $bankResponse->assertRedirect();
        $bankResponse->assertSessionHas('error');

        $expatriatesResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.expatriates.export',
            ['reference_date' => now()->toDateString(), 'window_days' => 30]
        ));

        $expatriatesResponse->assertRedirect();
        $expatriatesResponse->assertSessionHas('error');
    }

    public function test_bank_payment_file_export_returns_bank_columns_and_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Pagamento');

        Employee::query()->create([
            'employee_id' => 'EMP-BANK-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '411223344',
            'employment_type' => 'GENERAL',
            'basic_salary' => 28000,
            'account_holder_name' => 'Funcionario Pagamento',
            'bank_name' => 'Banco MZ',
            'bank_branch' => 'Maputo Sede',
            'bank_identifier_code' => 'BMAOMZMA',
            'account_number' => '000123456789',
            'creator_id' => $company->id,
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
            'total_gross_pay' => 28000,
            'total_deductions' => 1960,
            'total_net_pay' => 26040,
            'total_irps' => 0,
            'total_inss_employee' => 840,
            'total_inss_employer' => 1120,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 28000,
            'gross_pay' => 28000,
            'taxable_income' => 28000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 840,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1120,
            'statutory_deductions_total' => 1960,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1960,
            'total_loans' => 0,
            'net_pay' => 26040,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE BANK EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-BANK-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499001122',
            'employment_type' => 'GENERAL',
            'basic_salary' => 10000,
            'account_holder_name' => 'OUTSIDE BANK EMPLOYEE',
            'bank_name' => 'Other Bank',
            'bank_branch' => 'Other Branch',
            'bank_identifier_code' => 'OTHRXXYY',
            'account_number' => '999999999',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $otherPayroll = Payroll::query()->create([
            'title' => 'Payroll Maio Empresa B',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => '2026-05-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 10000,
            'total_deductions' => 700,
            'total_net_pay' => 9300,
            'total_irps' => 0,
            'total_inss_employee' => 300,
            'total_inss_employer' => 400,
            'employee_count' => 1,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $otherPayroll->id,
            'employee_id' => $otherEmployeeUser->id,
            'basic_salary' => 10000,
            'gross_pay' => 10000,
            'taxable_income' => 10000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 300,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 400,
            'statutory_deductions_total' => 700,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 700,
            'total_loans' => 0,
            'net_pay' => 9300,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.bank-payment-file.export',
            ['reference_period' => '2026-05']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Payment Reference', false);
        $response->assertSee('Account Holder Name', false);
        $response->assertSee('Bank Identifier Code', false);
        $response->assertSee('Funcionario Pagamento', false);
        $response->assertSee('000123456789', false);
        $response->assertDontSee('OUTSIDE BANK EMPLOYEE', false);
    }

    public function test_expatriates_export_returns_compliance_columns_and_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Estrangeiro');

        Employee::query()->create([
            'employee_id' => 'EMP-FOREIGN-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '433445566',
            'employment_type' => 'GENERAL',
            'basic_salary' => 45000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employee = Employee::query()
            ->where('user_id', $employeeUser->id)
            ->where('created_by', $company->id)
            ->firstOrFail();

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'passport_number' => 'P9988776',
            'passport_expires_at' => now()->addDays(50)->toDateString(),
            'visa_type' => 'work',
            'visa_expires_at' => now()->addDays(10)->toDateString(),
            'work_authorization_number' => 'AUT-2026-77',
            'work_authorization_expires_at' => now()->addDays(20)->toDateString(),
            'hiring_regime' => 'quota',
            'work_province' => 'Maputo',
            'cessation_effective_date' => now()->subDays(7)->toDateString(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $contractType = ContractType::query()->create([
            'name' => 'Labour Foreign',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Contract::query()->create([
            'subject' => 'Contrato expatriado',
            'user_id' => $employeeUser->id,
            'value' => 45000,
            'type_id' => $contractType->id,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'status' => 'active',
            'source_type' => 'contract',
            'is_labour_contract' => true,
            'legal_contract_type' => 'foreign',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE FOREIGN EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-FOREIGN-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499112233',
            'employment_type' => 'GENERAL',
            'basic_salary' => 30000,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $otherEmployee = Employee::query()
            ->where('user_id', $otherEmployeeUser->id)
            ->where('created_by', $otherCompany->id)
            ->firstOrFail();

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $otherEmployee->id,
            'is_foreign_worker' => true,
            'nationality' => 'ZA',
            'residency_status' => 'non_resident',
            'passport_number' => 'OUT-6677',
            'passport_expires_at' => now()->addDays(8)->toDateString(),
            'visa_type' => 'work',
            'visa_expires_at' => now()->addDays(6)->toDateString(),
            'work_authorization_number' => 'OUT-12',
            'work_authorization_expires_at' => now()->addDays(6)->toDateString(),
            'hiring_regime' => 'quota',
            'work_province' => 'Maputo',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.expatriates.export',
            ['reference_date' => now()->toDateString(), 'window_days' => 30]
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Quota Slots', false);
        $response->assertSee('Work Authorizations Expiring', false);
        $response->assertSee('Migration Notifications Pending', false);
        $response->assertSee('Employee Internal ID', false);
        $response->assertSee('Funcionario Estrangeiro', false);
        $response->assertSee('EMP-FOREIGN-001', false);
        $response->assertDontSee('OUTSIDE FOREIGN EMPLOYEE', false);
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
