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
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Shift;

class HrmPayrollComplianceImportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_api_import_endpoints_process_workforce_attendance_and_annual_leave_csv(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $employeeUser = $this->makeEmployeeUser($company, 'API Import Employee');
        $shift = Shift::query()->create([
            'shift_name' => 'Turno API',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employee = Employee::query()->create([
            'employee_id' => 'EMP-API-001',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $workforceCsv = implode("\n", [
            'Employee Record ID,Employee Internal ID,Employee NUIT,Employment Type,Basic Salary',
            $employee->id . ',EMP-API-001,400777666,SPECIAL,22000',
        ]);
        $workforceFile = UploadedFile::fake()->createWithContent('workforce_import.csv', $workforceCsv);

        $workforceResponse = $this->actingAs($company, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/workforce',
            ['csv_file' => $workforceFile]
        );

        $workforceResponse->assertOk();
        $workforceResponse->assertJsonPath('success', true);
        $workforceResponse->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'tax_payer_id' => '400777666',
            'employment_type' => 'SPECIAL',
            'basic_salary' => 22000,
        ]);

        $attendanceCsv = implode("\n", [
            'Employee Internal ID,Attendance Date,Clock In,Clock Out,Break Hours,Status,Source Channel,Source Device ID,Source Reference',
            'EMP-API-001,2026-05-28,2026-05-28 08:00,2026-05-28 17:00,1,present,biometric,DEV-API,BIO-REF-API',
        ]);
        $attendanceFile = UploadedFile::fake()->createWithContent('attendance_import.csv', $attendanceCsv);

        $attendanceResponse = $this->actingAs($company, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/attendance',
            ['csv_file' => $attendanceFile]
        );

        $attendanceResponse->assertOk();
        $attendanceResponse->assertJsonPath('success', true);
        $attendanceResponse->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->user_id,
            'status' => 'present',
            'source_channel' => 'biometric',
            'source_device_id' => 'DEV-API',
            'source_reference' => 'BIO-REF-API',
            'created_by' => $company->id,
        ]);

        $attendance = Attendance::query()
            ->where('created_by', $company->id)
            ->where('employee_id', $employee->user_id)
            ->firstOrFail();
        $this->assertSame('8.00', number_format((float) $attendance->total_hour, 2, '.', ''));

        $leaveType = LeaveType::query()->create([
            'name' => 'Férias Anuais',
            'legal_code' => 'annual',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $annualLeaveCsv = implode("\n", [
            'Employee Record ID,Leave Type ID,Leave Year,Planned Start Date,Planned End Date,Status,Notes',
            $employee->id . ',' . $leaveType->id . ',2026,2026-09-01,2026-09-10,approved,Plano API',
        ]);
        $annualLeaveFile = UploadedFile::fake()->createWithContent('annual_leave_plan_import.csv', $annualLeaveCsv);

        $annualLeaveResponse = $this->actingAs($company, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/annual-leave-plans',
            ['csv_file' => $annualLeaveFile]
        );

        $annualLeaveResponse->assertOk();
        $annualLeaveResponse->assertJsonPath('success', true);
        $annualLeaveResponse->assertJsonPath('data.updated', 1);

        $plan = AnnualLeavePlan::query()
            ->where('created_by', $company->id)
            ->where('employee_id', $employee->user_id)
            ->where('leave_type_id', $leaveType->id)
            ->where('leave_year', 2026)
            ->firstOrFail();

        $this->assertSame(10, (int) $plan->planned_days);
        $this->assertSame(AnnualLeavePlan::STATUS_APPROVED, (string) $plan->status);
        $this->assertSame('2026-09-01', Carbon::parse((string) $plan->planned_start_date)->toDateString());
        $this->assertSame('2026-09-10', Carbon::parse((string) $plan->planned_end_date)->toDateString());
    }

    public function test_api_import_endpoints_require_edit_payroll_permission(): void
    {
        $company = $this->makeCompany();
        $file = UploadedFile::fake()->createWithContent('workforce_import.csv', "Employee Record ID\n1");

        $response = $this->actingAs($company, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/workforce',
            ['csv_file' => $file]
        );

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_api_import_and_export_are_scoped_to_the_authenticated_company(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-payrolls', 'view-payrolls']);

        $employeeUserA = $this->makeEmployeeUser($companyA, 'Company A Employee');
        $employeeA = Employee::query()->create([
            'employee_id' => 'EMP-A-001',
            'user_id' => $employeeUserA->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $employeeUserB = $this->makeEmployeeUser($companyB, 'Company B Employee');
        $employeeB = Employee::query()->create([
            'employee_id' => 'EMP-B-001',
            'user_id' => $employeeUserB->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 18000,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $exportResponse = $this->actingAs($companyA, 'sanctum')->get(route(
            'hrm.mozambique-payroll-compliance.reports.workforce-register.json',
            ['reference_date' => '2026-05-31']
        ));

        $exportResponse->assertOk();
        $exportResponse->assertJsonPath('summary.workers_total', 1);
        $exportResponse->assertJsonCount(1, 'rows');
        $exportResponse->assertJsonPath('rows.0.employee_name', 'Company A Employee');
        $exportResponse->assertJsonMissingPath('rows.1');

        $workforceCsv = implode("\n", [
            'Employee Record ID,Employee Internal ID,Employee NUIT,Employment Type,Basic Salary',
            $employeeB->id . ',EMP-B-001,400999111,SPECIAL,22000',
        ]);
        $workforceFile = UploadedFile::fake()->createWithContent('workforce_import.csv', $workforceCsv);

        $workforceResponse = $this->actingAs($companyA, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/workforce',
            ['csv_file' => $workforceFile]
        );

        $workforceResponse->assertOk();
        $workforceResponse->assertJsonPath('success', true);
        $workforceResponse->assertJsonPath('data.processed', 1);
        $workforceResponse->assertJsonPath('data.updated', 0);
        $workforceResponse->assertJsonPath('data.skipped', 1);
        $workforceResponse->assertJsonPath('data.errors.0.message', 'Employee not found for supplied identifiers.');

        $this->assertDatabaseHas('employees', [
            'id' => $employeeB->id,
            'employee_id' => 'EMP-B-001',
            'employment_type' => 'GENERAL',
            'basic_salary' => 18000,
            'created_by' => $companyB->id,
        ]);

        $attendanceCsv = implode("\n", [
            'Employee Record ID,Attendance Date,Clock In,Clock Out,Break Hours,Status,Source Channel,Source Device ID,Source Reference',
            $employeeB->id . ',2026-05-28,2026-05-28 08:00,2026-05-28 17:00,1,present,biometric,DEV-B,BIO-REF-B',
        ]);
        $attendanceFile = UploadedFile::fake()->createWithContent('attendance_import.csv', $attendanceCsv);

        $attendanceResponse = $this->actingAs($companyA, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/attendance',
            ['csv_file' => $attendanceFile]
        );

        $attendanceResponse->assertOk();
        $attendanceResponse->assertJsonPath('data.processed', 1);
        $attendanceResponse->assertJsonPath('data.updated', 0);
        $attendanceResponse->assertJsonPath('data.skipped', 1);
        $attendanceResponse->assertJsonPath('data.errors.0.message', 'Employee not found for supplied identifiers.');
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employeeB->user_id,
            'created_by' => $companyA->id,
            'source_reference' => 'BIO-REF-B',
        ]);

        $annualLeaveCsv = implode("\n", [
            'Employee Record ID,Leave Type ID,Leave Year,Planned Start Date,Planned End Date,Status,Notes',
            $employeeB->id . ',999,2026,2026-09-01,2026-09-10,approved,Cross-company leave attempt',
        ]);
        $annualLeaveFile = UploadedFile::fake()->createWithContent('annual_leave_plan_import.csv', $annualLeaveCsv);

        $annualLeaveResponse = $this->actingAs($companyA, 'sanctum')->post(
            '/api/hrm/payroll-compliance/import/annual-leave-plans',
            ['csv_file' => $annualLeaveFile]
        );

        $annualLeaveResponse->assertOk();
        $annualLeaveResponse->assertJsonPath('data.processed', 1);
        $annualLeaveResponse->assertJsonPath('data.updated', 0);
        $annualLeaveResponse->assertJsonPath('data.skipped', 1);
        $annualLeaveResponse->assertJsonPath('data.errors.0.message', 'Employee not found for supplied identifiers.');
        $this->assertDatabaseMissing('annual_leave_plans', [
            'employee_id' => $employeeB->user_id,
            'created_by' => $companyA->id,
            'leave_year' => 2026,
        ]);
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
