<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractType;
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDependent;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;
use Workdo\Hrm\Models\EmployeeSocialSecurityProfile;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;
use Workdo\Hrm\Models\Shift;

class HrmWorkforceRegisterExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_workforce_register_csv_export_contains_legal_profiles_and_contract_data(): void
    {
        $company = $this->makeCompany();
        $outsiderCompany = $this->makeCompany();
        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);

        $mainEmployee = $this->createEmployeeWithLegalProfiles($company, 'EMP-WR-001', 'Funcionario Registo');
        $this->createEmployeeWithLegalProfiles($outsiderCompany, 'EMP-WR-OUT', 'Funcionario Fora');

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.workforce-register.export',
            ['reference_date' => '2026-05-31']
        ));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Employee Internal ID', false);
        $response->assertSee('INSS Number', false);
        $response->assertSee('Contract Number', false);
        $response->assertSee('EMP-WR-001', false);
        $response->assertSee('Funcionario Registo', false);
        $response->assertSee('INSS-999100', false);
        $response->assertSee('CTR-WR-0001', false);
        $response->assertSee('Dependents Total', false);
        $response->assertDontSee('EMP-WR-OUT', false);
        $response->assertDontSee('Funcionario Fora', false);

        $this->assertDatabaseHas('employee_social_security_profiles', [
            'employee_id' => $mainEmployee->id,
            'inss_number' => 'INSS-999100',
            'created_by' => $company->id,
        ]);
    }

    public function test_workforce_register_json_and_xml_exports_return_structured_payloads(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-payrolls', 'view-sensitive-employee-data']);
        $this->createEmployeeWithLegalProfiles($company, 'EMP-WR-JSON', 'Funcionario JSON');

        $jsonResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.workforce-register.json',
            ['reference_date' => '2026-05-31']
        ));

        $jsonResponse->assertOk();
        $jsonResponse->assertJsonStructure([
            'reference_date',
            'summary' => [
                'workers_total',
                'workers_with_inss',
                'workers_without_inss',
                'foreign_workers',
                'dependents_total',
            ],
            'rows',
        ]);
        $jsonResponse->assertJsonPath('reference_date', '2026-05-31');
        $jsonResponse->assertJsonPath('summary.workers_total', 1);
        $jsonResponse->assertSee('Funcionario JSON', false);

        $xmlResponse = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.workforce-register.xml',
            ['reference_date' => '2026-05-31']
        ));

        $xmlResponse->assertOk();
        $xmlResponse->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xmlResponse->assertSee('<hr_workforce_register>', false);
        $xmlResponse->assertSee('<workers_total>1</workers_total>', false);
        $xmlResponse->assertSee('<employee_name>Funcionario JSON</employee_name>', false);
    }

    public function test_workforce_register_export_masks_sensitive_fields_without_sensitive_permission(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['view-payrolls']);
        $this->createEmployeeWithLegalProfiles($company, 'EMP-WR-MASK', 'Funcionario Masked');

        $response = $this->actingAs($company)->get(route(
            'hrm.mozambique-payroll-compliance.reports.workforce-register.export',
            ['reference_date' => '2026-05-31']
        ));

        $response->assertOk();
        $response->assertDontSee('400123456', false);
        $response->assertDontSee('INSS-999100', false);
        $response->assertDontSee('PT123456', false);
        $response->assertSee('***', false);
    }

    public function test_workforce_register_import_updates_employee_legal_profiles(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $employeeUser = User::factory()->create([
            'type' => 'staff',
            'name' => 'Funcionario Import',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $employee = Employee::query()->create([
            'employee_id' => 'EMP-WR-IMP-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $csv = implode("\n", [
            'Employee Record ID,Employee Internal ID,Employee NUIT,Employment Type,Basic Salary,INSS Number,INSS Status,INSS Registration Date,Is Foreign Worker,Nationality,Residency Status,Hiring Regime,Work Province,Passport Number,Passport Expires At,Visa Type,Visa Expires At,Work Authorization Number,Work Authorization Expires At,Probation Category,Probation Starts At,Probation Expected End,Probation Evaluation,Probation Decision,Probation Decision Date',
            $employee->id . ',EMP-WR-IMP-001,400111222,SPECIAL,22000,INSS-IMPORT-01,registered,2026-01-10,yes,ZA,non_resident,quota,Maputo,PASS-9988,2027-12-31,work,2027-06-30,AUTH-5500,2027-06-30,technician_mid,2026-02-01,2026-04-30,completed,confirmed,2026-04-28',
        ]);

        $file = UploadedFile::fake()->createWithContent('workforce_import.csv', $csv);

        $response = $this->actingAs($company)->post(
            route('hrm.mozambique-payroll-compliance.reports.workforce-register.import'),
            ['csv_file' => $file]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'tax_payer_id' => '400111222',
            'employment_type' => 'SPECIAL',
            'basic_salary' => 22000,
        ]);

        $this->assertDatabaseHas('employee_social_security_profiles', [
            'employee_id' => $employee->id,
            'inss_number' => 'INSS-IMPORT-01',
            'registration_status' => 'registered',
            'created_by' => $company->id,
        ]);

        $this->assertDatabaseHas('employee_foreign_worker_profiles', [
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
            'nationality' => 'ZA',
            'hiring_regime' => 'quota',
            'created_by' => $company->id,
        ]);

        $this->assertDatabaseHas('employee_probation_profiles', [
            'employee_id' => $employee->id,
            'probation_category' => 'technician_mid',
            'evaluation_status' => 'completed',
            'decision_status' => 'confirmed',
            'created_by' => $company->id,
        ]);
    }

    public function test_attendance_import_creates_or_updates_records(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $employee = $this->createEmployeeWithLegalProfiles($company, 'EMP-AT-IMP-001', 'Funcionario Attendance');
        $shift = Shift::query()->create([
            'shift_name' => 'Turno Geral',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        $employee->shift = $shift->id;
        $employee->save();

        $csv = implode("\n", [
            'Employee Internal ID,Attendance Date,Clock In,Clock Out,Break Hours,Status,Is Justified,Absence Category,Notes,Source Channel,Source Device ID,Source Device Label,Source Reference',
            'EMP-AT-IMP-001,2026-05-20,2026-05-20 08:00,2026-05-20 17:00,1,present,yes,legal_leave,Imported attendance,biometric,DEV-001,Main Gate,BIO-REF-1001',
        ]);
        $file = UploadedFile::fake()->createWithContent('attendance_import.csv', $csv);

        $response = $this->actingAs($company)->post(
            route('hrm.mozambique-payroll-compliance.reports.attendance-compliance.import'),
            ['csv_file' => $file]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->user_id,
            'status' => 'present',
            'source_channel' => 'biometric',
            'source_device_id' => 'DEV-001',
            'source_reference' => 'BIO-REF-1001',
            'created_by' => $company->id,
        ]);

        $attendance = Attendance::query()
            ->where('created_by', $company->id)
            ->where('employee_id', $employee->user_id)
            ->whereDate('date', '2026-05-20')
            ->firstOrFail();

        $this->assertSame('2026-05-20 08:00:00', Carbon::parse((string) $attendance->clock_in)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-05-20 17:00:00', Carbon::parse((string) $attendance->clock_out)->format('Y-m-d H:i:s'));
        $this->assertSame('8.00', number_format((float) $attendance->total_hour, 2, '.', ''));
    }

    public function test_annual_leave_plan_import_creates_or_updates_records(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $employee = $this->createEmployeeWithLegalProfiles($company, 'EMP-LP-IMP-001', 'Funcionario Leave Plan');

        $leaveType = LeaveType::query()->create([
            'name' => 'Férias Anuais',
            'legal_code' => 'annual',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $csv = implode("\n", [
            'Employee Record ID,Leave Type ID,Leave Year,Planned Start Date,Planned End Date,Status,Notes,Manager Approved At,HR Approved At',
            $employee->id . ',' . $leaveType->id . ',2026,2026-08-01,2026-08-10,approved,Imported annual plan,2026-06-15 09:00,2026-06-20 11:00',
        ]);
        $file = UploadedFile::fake()->createWithContent('annual_leave_plan_import.csv', $csv);

        $response = $this->actingAs($company)->post(
            route('hrm.mozambique-payroll-compliance.reports.annual-leave-compliance.import'),
            ['csv_file' => $file]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('annual_leave_plans', [
            'employee_id' => $employee->user_id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_days' => 10,
            'status' => AnnualLeavePlan::STATUS_APPROVED,
            'created_by' => $company->id,
        ]);

        $plan = AnnualLeavePlan::query()
            ->where('created_by', $company->id)
            ->where('employee_id', $employee->user_id)
            ->where('leave_type_id', $leaveType->id)
            ->where('leave_year', 2026)
            ->firstOrFail();

        $this->assertSame('2026-08-01', Carbon::parse((string) $plan->planned_start_date)->toDateString());
        $this->assertSame('2026-08-10', Carbon::parse((string) $plan->planned_end_date)->toDateString());
    }

    private function createEmployeeWithLegalProfiles(User $company, string $employeeCode, string $employeeName): Employee
    {
        $employeeUser = User::factory()->create([
            'type' => 'staff',
            'name' => $employeeName,
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

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
            'designation_name' => 'Analista Fiscal',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employee = Employee::query()->create([
            'employee_id' => $employeeCode,
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'date_of_joining' => '2026-01-01',
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400123456',
            'basic_salary' => 35000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeDependent::query()->create([
            'employee_id' => $employee->id,
            'full_name' => 'Dependente Fiscal',
            'relationship' => 'child',
            'date_of_birth' => '2015-03-20',
            'is_tax_eligible' => true,
            'is_student' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeSocialSecurityProfile::query()->create([
            'employee_id' => $employee->id,
            'inss_number' => 'INSS-999100',
            'registration_date' => '2026-01-05',
            'registration_status' => 'registered',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'passport_number' => 'PT123456',
            'passport_expires_at' => '2027-12-31',
            'visa_type' => 'work',
            'visa_expires_at' => '2027-06-30',
            'work_authorization_number' => 'AUTH-7788',
            'work_authorization_expires_at' => '2027-06-30',
            'hiring_regime' => 'quota',
            'work_province' => 'Maputo',
            'mozambique_entry_date' => '2026-01-02',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeProbationProfile::query()->create([
            'employee_id' => $employee->id,
            'probation_category' => 'technician_mid',
            'starts_at' => '2026-01-01',
            'expected_end_at' => '2026-03-31',
            'legal_max_days' => 90,
            'evaluation_status' => 'completed',
            'decision_status' => 'confirmed',
            'decision_date' => '2026-03-29',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $contractType = ContractType::query()->create([
            'name' => 'Labour Contract',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Contract::query()->create([
            'subject' => 'Contrato de Trabalho',
            'user_id' => $employeeUser->id,
            'value' => 35000,
            'type_id' => $contractType->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'description' => 'Contrato laboral para teste de export',
            'status' => 'active',
            'source_type' => 'contract',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'fixed_term_justification' => 'Substituicao temporaria',
            'contract_number' => 'CTR-WR-0001',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-11',
            'total_days' => 2,
            'reason' => 'Licenca anual',
            'status' => 'approved',
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
            'total_gross_pay' => 37000,
            'total_deductions' => 3500,
            'total_net_pay' => 33500,
            'total_irps' => 1200,
            'total_inss_employee' => 1050,
            'total_inss_employer' => 1400,
            'employee_count' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'basic_salary' => 35000,
            'gross_pay' => 37000,
            'net_pay' => 33500,
            'taxable_income' => 37000,
            'irps_amount' => 1200,
            'inss_employee_rate' => 3,
            'inss_employee_amount' => 1050,
            'inss_employer_rate' => 4,
            'inss_employer_amount' => 1400,
            'statutory_deductions_total' => 2250,
            'total_allowances' => 2000,
            'total_manual_overtimes' => 0,
            'total_deductions' => 3500,
            'total_loans' => 0,
            'working_days' => 22,
            'status' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return $employee;
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
