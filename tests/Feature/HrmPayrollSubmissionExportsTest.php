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
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDependent;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\Warning;
use Workdo\Training\Models\Training;
use Workdo\Training\Models\TrainingTask;
use Workdo\Training\Models\TrainingType;

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

        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);

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

        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);

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

        $leaveResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.annual-leave-compliance.export',
            ['reference_date' => now()->toDateString()]
        ));

        $leaveResponse->assertRedirect();
        $leaveResponse->assertSessionHas('error');

        $attendanceResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.attendance-compliance.export',
            ['reference_period' => now()->format('Y-m')]
        ));

        $attendanceResponse->assertRedirect();
        $attendanceResponse->assertSessionHas('error');

        $trainingResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.training-compliance.export',
            ['reference_date' => now()->toDateString(), 'window_days' => 30]
        ));

        $trainingResponse->assertRedirect();
        $trainingResponse->assertSessionHas('error');
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

        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);

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

    public function test_bank_payment_file_masks_sensitive_data_without_sensitive_permission(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Mask');

        Employee::query()->create([
            'employee_id' => 'EMP-BANK-MASK-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '411998877',
            'employment_type' => 'GENERAL',
            'basic_salary' => 21000,
            'account_holder_name' => 'Funcionario Mask',
            'bank_name' => 'Banco MZ',
            'bank_branch' => 'Maputo Sede',
            'bank_identifier_code' => 'BMAOMZMA',
            'account_number' => '000111222333',
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
            'total_gross_pay' => 21000,
            'total_deductions' => 1470,
            'total_net_pay' => 19530,
            'total_irps' => 0,
            'total_inss_employee' => 630,
            'total_inss_employer' => 840,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 21000,
            'gross_pay' => 21000,
            'taxable_income' => 21000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 630,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 840,
            'statutory_deductions_total' => 1470,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1470,
            'total_loans' => 0,
            'net_pay' => 19530,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.bank-payment-file.export',
            ['reference_period' => '2026-05']
        ));

        $response->assertOk();
        $response->assertDontSee('000111222333', false);
        $response->assertDontSee('411998877', false);
        $response->assertSee('***', false);
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

    public function test_disciplinary_report_export_returns_case_columns_and_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Disciplina');

        Employee::query()->create([
            'employee_id' => 'EMP-DISC-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '466778899',
            'employment_type' => 'GENERAL',
            'basic_salary' => 32000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Warning::query()->create([
            'employee_id' => $employeeUser->id,
            'subject' => 'Atrasos recorrentes',
            'severity' => 'high',
            'warning_date' => '2026-05-10',
            'response_deadline_at' => '2026-05-15',
            'decision_deadline_at' => '2026-05-25',
            'description' => 'Registo disciplinar de atrasos reiterados.',
            'warning_by' => $company->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Complaint::query()->create([
            'employee_id' => $employeeUser->id,
            'subject' => 'Denuncia interna',
            'description' => 'Queixa formal em analise.',
            'complaint_date' => '2026-05-12',
            'status' => 'in review',
            'is_harassment_report' => true,
            'is_confidential' => true,
            'confidentiality_level' => 'restricted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE DISCIPLINARY EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-DISC-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499778899',
            'employment_type' => 'GENERAL',
            'basic_salary' => 22000,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        Warning::query()->create([
            'employee_id' => $otherEmployeeUser->id,
            'subject' => 'Outside warning',
            'severity' => 'medium',
            'warning_date' => '2026-05-11',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        Complaint::query()->create([
            'employee_id' => $otherEmployeeUser->id,
            'subject' => 'Outside complaint',
            'description' => 'External tenant complaint.',
            'complaint_date' => '2026-05-11',
            'status' => 'pending',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $csvResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.disciplinary-cases.export',
            ['reference_period' => '2026-05']
        ));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csvResponse->assertSee('Case Reference', false);
        $csvResponse->assertSee('Harassment Case', false);
        $csvResponse->assertSee('Response Deadline Overdue', false);
        $csvResponse->assertSee('Funcionario Disciplina', false);
        $csvResponse->assertSee('46*****99', false);
        $csvResponse->assertDontSee('466778899', false);
        $csvResponse->assertDontSee('OUTSIDE DISCIPLINARY EMPLOYEE', false);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.disciplinary-cases.json',
            ['reference_period' => '2026-05']
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('reference_period', '2026-05');
        $jsonResponse->assertJsonPath('summary.cases_total', 2);
        $jsonResponse->assertJsonPath('summary.disciplinary_cases_total', 1);
        $jsonResponse->assertJsonPath('summary.harassment_cases_total', 1);
        collect((array) $jsonResponse->json('rows'))->each(function (array $row): void {
            $this->assertSame('46*****99', $row['employee_nuit'] ?? null);
        });
    }

    public function test_disciplinary_report_export_exposes_sensitive_data_with_sensitive_permission(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Disciplina Sensivel');

        Employee::query()->create([
            'employee_id' => 'EMP-DISC-SENS-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '488990011',
            'employment_type' => 'GENERAL',
            'basic_salary' => 32000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Warning::query()->create([
            'employee_id' => $employeeUser->id,
            'subject' => 'Atrasos sensiveis',
            'severity' => 'high',
            'warning_date' => '2026-05-10',
            'response_deadline_at' => '2026-05-15',
            'decision_deadline_at' => '2026-05-25',
            'description' => 'Registo disciplinar sensivel.',
            'warning_by' => $company->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);

        $csvResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.disciplinary-cases.export',
            ['reference_period' => '2026-05']
        ));

        $csvResponse->assertOk();
        $csvResponse->assertSee('488990011', false);
        $csvResponse->assertDontSee('48*****11', false);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.disciplinary-cases.json',
            ['reference_period' => '2026-05']
        ));

        $jsonResponse->assertOk();
        collect((array) $jsonResponse->json('rows'))->each(function (array $row): void {
            $this->assertSame('488990011', $row['employee_nuit'] ?? null);
        });
    }

    public function test_annual_leave_report_export_returns_leave_columns_and_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Ferias Relatorio');

        Employee::query()->create([
            'employee_id' => 'EMP-LEAVE-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '477889900',
            'employment_type' => 'GENERAL',
            'date_of_joining' => '2024-01-10',
            'basic_salary' => 35000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $annualLeaveType = LeaveType::query()->create([
            'name' => 'Férias Anuais',
            'legal_code' => 'annual',
            'description' => 'Ferias anuais',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'allow_cash_out' => true,
            'min_effective_rest_days' => 6,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $annualLeaveType->id,
            'start_date' => '2026-03-03',
            'end_date' => '2026-03-10',
            'total_days' => 8,
            'compensated_days' => 2,
            'effective_rest_days' => 6,
            'reason' => 'Férias anuais',
            'status' => 'approved',
            'approved_by' => $company->id,
            'approved_at' => '2026-02-20 10:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $annualLeaveType->id,
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-14',
            'total_days' => 5,
            'compensated_days' => 0,
            'effective_rest_days' => 5,
            'reason' => 'Férias agendadas',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $annualLeaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-12-10',
            'planned_end_date' => '2026-12-20',
            'planned_days' => 11,
            'status' => AnnualLeavePlan::STATUS_PENDING_HR,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE LEAVE EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-LEAVE-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499889900',
            'employment_type' => 'GENERAL',
            'basic_salary' => 20000,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $otherLeaveType = LeaveType::query()->create([
            'name' => 'Annual Leave B',
            'legal_code' => 'annual',
            'description' => 'Outside leave type',
            'max_days_per_year' => 22,
            'is_paid' => true,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $otherEmployeeUser->id,
            'leave_type_id' => $otherLeaveType->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-04',
            'total_days' => 4,
            'reason' => 'Outside leave',
            'status' => 'approved',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $csvResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.annual-leave-compliance.export',
            ['reference_date' => '2026-06-01']
        ));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csvResponse->assertSee('Annual Leave Entitlement', false);
        $csvResponse->assertSee('Annual Leave Compensated', false);
        $csvResponse->assertSee('Annual Leave Planned Pending', false);
        $csvResponse->assertSee('Funcionario Ferias Relatorio', false);
        $csvResponse->assertSee('477889900', false);
        $csvResponse->assertDontSee('OUTSIDE LEAVE EMPLOYEE', false);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.annual-leave-compliance.json',
            ['reference_date' => '2026-06-01']
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('reference_year', 2026);
        $jsonResponse->assertJsonPath('summary.workers_total', 1);
        $jsonResponse->assertJsonPath('summary.compensated_leave_days_total', 2);
        $jsonResponse->assertJsonPath('summary.annual_leave_planned_days_total_current_year', 11);
    }

    public function test_attendance_compliance_report_export_returns_attendance_columns_and_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Assiduidade');

        Employee::query()->create([
            'employee_id' => 'EMP-ATT-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '488990011',
            'employment_type' => 'GENERAL',
            'basic_salary' => 28000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $dayShift = Shift::query()->create([
            'shift_name' => 'Day Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_night_shift' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $nightShift = Shift::query()->create([
            'shift_name' => 'Night Shift',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'is_night_shift' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $dayShift->id,
            'date' => '2026-05-05',
            'clock_in' => '2026-05-05 08:15:00',
            'clock_out' => '2026-05-05 17:30:00',
            'break_hour' => 1,
            'total_hour' => 8.25,
            'overtime_hours' => 0.5,
            'overtime_amount' => 250,
            'status' => 'present',
            'is_justified' => false,
            'absence_category' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $dayShift->id,
            'date' => '2026-05-06',
            'clock_in' => '2026-05-06 08:00:00',
            'clock_out' => null,
            'break_hour' => 0,
            'total_hour' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'absent',
            'is_justified' => true,
            'absence_category' => 'sickness',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $dayShift->id,
            'date' => '2026-05-07',
            'clock_in' => '2026-05-07 08:00:00',
            'clock_out' => null,
            'break_hour' => 0,
            'total_hour' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'absent',
            'is_justified' => false,
            'absence_category' => 'unjustified',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $nightShift->id,
            'date' => '2026-05-08',
            'clock_in' => '2026-05-08 22:00:00',
            'clock_out' => '2026-05-09 06:00:00',
            'break_hour' => 0,
            'total_hour' => 8,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'present',
            'is_justified' => false,
            'absence_category' => null,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE ATTENDANCE EMPLOYEE');

        Shift::query()->create([
            'shift_name' => 'Outside Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_night_shift' => false,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'EMP-ATT-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499990011',
            'employment_type' => 'GENERAL',
            'basic_salary' => 18000,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $outsideShift = Shift::query()
            ->where('created_by', $otherCompany->id)
            ->firstOrFail();

        Attendance::query()->create([
            'employee_id' => $otherEmployeeUser->id,
            'shift_id' => $outsideShift->id,
            'date' => '2026-05-10',
            'clock_in' => '2026-05-10 08:00:00',
            'clock_out' => '2026-05-10 17:00:00',
            'break_hour' => 1,
            'total_hour' => 8,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'present',
            'is_justified' => false,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $csvResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.attendance-compliance.export',
            ['reference_period' => '2026-05']
        ));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csvResponse->assertSee('Weekly Rest Breach Risk', false);
        $csvResponse->assertSee('Anomaly Missing Clock Out', false);
        $csvResponse->assertSee('Night Work Minutes', false);
        $csvResponse->assertSee('Funcionario Assiduidade', false);
        $csvResponse->assertDontSee('OUTSIDE ATTENDANCE EMPLOYEE', false);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.attendance-compliance.json',
            ['reference_period' => '2026-05']
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('reference_period', '2026-05');
        $jsonResponse->assertJsonPath('summary.attendance_records_total', 4);
        $jsonResponse->assertJsonPath('summary.presences_total', 2);
        $jsonResponse->assertJsonPath('summary.absences_total', 2);
        $jsonResponse->assertJsonPath('summary.absences_justified_total', 1);
        $jsonResponse->assertJsonPath('summary.absences_unjustified_total', 1);
        $jsonResponse->assertJsonPath('summary.night_work_records_total', 1);
        $jsonResponse->assertJsonPath('summary.attendance_anomalies_total', 3);
    }

    public function test_training_compliance_report_export_returns_mandatory_training_columns_and_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Formacao');

        Employee::query()->create([
            'employee_id' => 'EMP-TRAIN-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '499771122',
            'employment_type' => 'GENERAL',
            'basic_salary' => 26000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $safetyTrainingType = TrainingType::query()->create([
            'name' => 'Seguranca no Trabalho',
            'description' => 'Treino obrigatorio SST',
            'is_mandatory' => true,
            'compliance_code' => 'SST',
            'certificate_validity_days' => 20,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        TrainingType::query()->create([
            'name' => 'Protecao de Dados',
            'description' => 'Treino obrigatorio privacidade',
            'is_mandatory' => true,
            'compliance_code' => 'PRIV',
            'certificate_validity_days' => 30,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        TrainingType::query()->create([
            'name' => 'Treino Opcional',
            'description' => 'Nao obrigatorio',
            'is_mandatory' => false,
            'certificate_validity_days' => 10,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $training = Training::query()->create([
            'title' => 'SST 2026',
            'description' => 'Sessao anual de SST',
            'training_type_id' => $safetyTrainingType->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-05',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'completed',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        TrainingTask::query()->create([
            'training_id' => $training->id,
            'title' => 'Concluir formacao SST',
            'description' => 'Formacao concluida',
            'status' => 'completed',
            'assigned_to' => $employeeUser->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE TRAINING EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-TRAIN-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '488661100',
            'employment_type' => 'GENERAL',
            'basic_salary' => 18000,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $otherTrainingType = TrainingType::query()->create([
            'name' => 'Outside Mandatory',
            'description' => 'Outside mandatory training',
            'is_mandatory' => true,
            'compliance_code' => 'OUT',
            'certificate_validity_days' => 15,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $otherTraining = Training::query()->create([
            'title' => 'Outside Training',
            'description' => 'Outside company training',
            'training_type_id' => $otherTrainingType->id,
            'start_date' => '2026-05-02',
            'end_date' => '2026-05-03',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        TrainingTask::query()->create([
            'training_id' => $otherTraining->id,
            'title' => 'Outside task',
            'status' => 'completed',
            'assigned_to' => $otherEmployeeUser->id,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $csvResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.training-compliance.export',
            ['reference_date' => '2026-05-10', 'window_days' => 30]
        ));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csvResponse->assertSee('Training Compliance Code', false);
        $csvResponse->assertSee('Certificate Expires At', false);
        $csvResponse->assertSee('Compliance Status', false);
        $csvResponse->assertSee('Funcionario Formacao', false);
        $csvResponse->assertSee('EMP-TRAIN-001', false);
        $csvResponse->assertDontSee('OUTSIDE TRAINING EMPLOYEE', false);

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.training-compliance.json',
            ['reference_date' => '2026-05-10', 'window_days' => 30]
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonPath('report_date', '2026-05-10');
        $jsonResponse->assertJsonPath('summary.workers_evaluated', 1);
        $jsonResponse->assertJsonPath('summary.mandatory_training_types_total', 2);
        $jsonResponse->assertJsonPath('summary.rows_total', 2);
        $jsonResponse->assertJsonPath('summary.rows_overdue', 1);
        $jsonResponse->assertJsonPath('summary.rows_expiring_soon', 1);
        $jsonResponse->assertJsonPath('summary.workers_with_overdue_mandatory_training', 1);
        $jsonResponse->assertJsonPath('summary.workers_with_expiring_mandatory_training', 1);
    }

    public function test_annual_fiscal_history_export_aggregates_worker_totals_for_selected_year(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Fiscal');

        Employee::query()->create([
            'employee_id' => 'EMP-FISCAL-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '455667788',
            'employment_type' => 'GENERAL',
            'basic_salary' => 24000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employee = Employee::query()
            ->where('user_id', $employeeUser->id)
            ->where('created_by', $company->id)
            ->firstOrFail();

        EmployeeDependent::query()->create([
            'employee_id' => $employee->id,
            'full_name' => 'Dependente Fiscal',
            'relationship' => 'child',
            'date_of_birth' => '2015-06-10',
            'is_student' => true,
            'is_tax_eligible' => true,
            'valid_until' => '2030-12-31',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $janPayroll = Payroll::query()->create([
            'title' => 'Payroll Janeiro 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-01-01',
            'pay_period_end' => '2026-01-31',
            'pay_date' => '2026-01-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 25000,
            'total_deductions' => 1800,
            'total_net_pay' => 23200,
            'total_irps' => 1050,
            'total_inss_employee' => 750,
            'total_inss_employer' => 1000,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $janPayroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 24000,
            'gross_pay' => 25000,
            'taxable_income' => 25000,
            'irps_amount' => 1050,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 750,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1000,
            'statutory_deductions_total' => 1800,
            'total_allowances' => 1000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1800,
            'total_loans' => 0,
            'net_pay' => 23200,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $febPayroll = Payroll::query()->create([
            'title' => 'Payroll Fevereiro 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-02-01',
            'pay_period_end' => '2026-02-28',
            'pay_date' => '2026-02-28',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 28000,
            'total_deductions' => 2100,
            'total_net_pay' => 25900,
            'total_irps' => 1200,
            'total_inss_employee' => 840,
            'total_inss_employer' => 1120,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $febPayroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 24000,
            'gross_pay' => 28000,
            'taxable_income' => 28000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 840,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1120,
            'statutory_deductions_total' => 2100,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 2100,
            'total_loans' => 0,
            'net_pay' => 25900,
            'working_days' => 20,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Payroll::query()->create([
            'title' => 'Payroll Dezembro 2025',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2025-12-01',
            'pay_period_end' => '2025-12-31',
            'pay_date' => '2025-12-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_gross_pay' => 10000,
            'total_deductions' => 700,
            'total_net_pay' => 9300,
            'total_irps' => 0,
            'total_inss_employee' => 300,
            'total_inss_employer' => 400,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.annual-fiscal-history.export',
            ['fiscal_year' => '2026']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Fiscal Year', false);
        $response->assertSee('Gross Pay Total', false);
        $response->assertSee('INSS Employer Total', false);
        $response->assertSee('Funcionario Fiscal', false);
        $response->assertSee('53000', false);
        $response->assertSee('2120', false);
        $response->assertDontSee('Payroll Dezembro 2025', false);
    }

    public function test_annual_fiscal_history_json_is_company_scoped(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Historico');

        Employee::query()->create([
            'employee_id' => 'EMP-HIST-001',
            'user_id' => $employeeUser->id,
            'tax_payer_id' => '466778899',
            'employment_type' => 'GENERAL',
            'basic_salary' => 18000,
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
            'total_gross_pay' => 18000,
            'total_deductions' => 1260,
            'total_net_pay' => 16740,
            'total_irps' => 0,
            'total_inss_employee' => 540,
            'total_inss_employer' => 720,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 18000,
            'gross_pay' => 18000,
            'taxable_income' => 18000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 540,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 720,
            'statutory_deductions_total' => 1260,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 1260,
            'total_loans' => 0,
            'net_pay' => 16740,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $otherCompany = $this->makeCompany();
        $otherEmployeeUser = $this->makeEmployeeUser($otherCompany, 'OUTSIDE HIST EMPLOYEE');

        Employee::query()->create([
            'employee_id' => 'EMP-HIST-OUT-001',
            'user_id' => $otherEmployeeUser->id,
            'tax_payer_id' => '499889977',
            'employment_type' => 'GENERAL',
            'basic_salary' => 11000,
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
            'total_gross_pay' => 11000,
            'total_deductions' => 770,
            'total_net_pay' => 10230,
            'total_irps' => 0,
            'total_inss_employee' => 330,
            'total_inss_employer' => 440,
            'employee_count' => 1,
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $otherPayroll->id,
            'employee_id' => $otherEmployeeUser->id,
            'basic_salary' => 11000,
            'gross_pay' => 11000,
            'taxable_income' => 11000,
            'irps_amount' => 0,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 330,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 440,
            'statutory_deductions_total' => 770,
            'total_allowances' => 0,
            'total_manual_overtimes' => 0,
            'total_deductions' => 770,
            'total_loans' => 0,
            'net_pay' => 10230,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $otherCompany->id,
            'created_by' => $otherCompany->id,
        ]);

        $this->grantPermissions($company, ['view-payrolls']);

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.annual-fiscal-history.json',
            ['fiscal_year' => '2026']
        ));

        $response->assertOk();
        $response->assertJsonPath('fiscal_year', '2026');
        $response->assertJsonPath('summary.workers', 1);
        $response->assertJsonPath('rows.0.employee_name', 'Funcionario Historico');
        $response->assertJsonPath('rows.0.gross_pay_total', 18000);
        $response->assertJsonMissing(['employee_name' => 'OUTSIDE HIST EMPLOYEE']);
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
