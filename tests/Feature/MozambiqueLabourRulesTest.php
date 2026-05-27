<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Overtime;

class MozambiqueLabourRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_company_can_update_mozambique_labour_policy_settings(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-payrolls']);

        $response = $this->actingAs($company)->put(route('hrm.mozambique-payroll-compliance.labour-policy.update'), [
            'overtime_daily_limit_hours' => 2,
            'overtime_weekly_limit_hours' => 12,
            'overtime_monthly_limit_hours' => 40,
            'overtime_quarterly_limit_hours' => 100,
            'overtime_yearly_limit_hours' => 200,
            'leave_min_notice_days' => 3,
            'leave_max_consecutive_days' => 30,
            'leave_count_non_working_days' => false,
            'leave_count_holidays' => false,
        ]);

        $response->assertRedirect();

        $this->assertSame('2', (string) $this->settingValue('mz_overtime_daily_limit_hours', $company->id));
        $this->assertSame('12', (string) $this->settingValue('mz_overtime_weekly_limit_hours', $company->id));
        $this->assertSame('40', (string) $this->settingValue('mz_overtime_monthly_limit_hours', $company->id));
        $this->assertSame('100', (string) $this->settingValue('mz_overtime_quarterly_limit_hours', $company->id));
        $this->assertSame('200', (string) $this->settingValue('mz_overtime_yearly_limit_hours', $company->id));
        $this->assertSame('3', (string) $this->settingValue('mz_leave_min_notice_days', $company->id));
        $this->assertSame('30', (string) $this->settingValue('mz_leave_max_consecutive_days', $company->id));
        $this->assertSame('0', (string) $this->settingValue('mz_leave_count_non_working_days', $company->id));
        $this->assertSame('0', (string) $this->settingValue('mz_leave_count_holidays', $company->id));
    }

    public function test_overtime_is_blocked_when_daily_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario OT');
        $employee = Employee::create([
            'employee_id' => 'OT-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'mz_overtime_daily_limit_hours', 'created_by' => $company->id],
            ['value' => '2', 'is_public' => 1]
        );

        $this->grantPermissions($company, ['create-overtimes']);

        $response = $this->actingAs($company)->post(route('hrm.overtimes.store'), [
            'employee_id' => $employee->id,
            'title' => 'Horas extra teste',
            'total_days' => 1,
            'hours' => 3,
            'rate' => 100,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'notes' => null,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('hours');
        $this->assertSame(0, Overtime::count());
    }

    public function test_overtime_uses_mozambique_legal_defaults_when_limits_are_not_configured(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario OT Default');
        $employee = Employee::create([
            'employee_id' => 'OT-DEFAULT-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['create-overtimes']);

        $response = $this->actingAs($company)->post(route('hrm.overtimes.store'), [
            'employee_id' => $employee->id,
            'title' => 'Horas extra limite legal',
            'total_days' => 1,
            'hours' => 5,
            'rate' => 100,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'notes' => null,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('hours');
        $this->assertSame(0, Overtime::count());
    }

    public function test_overtime_is_blocked_when_weekly_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario OT Semanal');
        $employee = Employee::create([
            'employee_id' => 'OT-W-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'mz_overtime_weekly_limit_hours', 'created_by' => $company->id],
            ['value' => '4', 'is_public' => 1]
        );

        Overtime::create([
            'title' => 'Horas extra semanais existentes',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 3,
            'rate' => 100,
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
            'status' => 'active',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['create-overtimes']);

        $response = $this->actingAs($company)->post(route('hrm.overtimes.store'), [
            'employee_id' => $employee->id,
            'title' => 'Horas extra semanais novas',
            'total_days' => 1,
            'hours' => 2,
            'rate' => 100,
            'start_date' => '2026-06-04',
            'end_date' => '2026-06-04',
            'notes' => null,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('hours');
        $this->assertSame(1, Overtime::count());
    }

    public function test_overtime_is_blocked_when_quarterly_limit_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario OT Trimestral');
        $employee = Employee::create([
            'employee_id' => 'OT-Q-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'mz_overtime_quarterly_limit_hours', 'created_by' => $company->id],
            ['value' => '6', 'is_public' => 1]
        );

        Overtime::create([
            'title' => 'Horas extra trimestrais existentes',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 5,
            'rate' => 100,
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-10',
            'status' => 'active',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->grantPermissions($company, ['create-overtimes']);

        $response = $this->actingAs($company)->post(route('hrm.overtimes.store'), [
            'employee_id' => $employee->id,
            'title' => 'Horas extra trimestrais novas',
            'total_days' => 1,
            'hours' => 2,
            'rate' => 100,
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15',
            'notes' => null,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('hours');
        $this->assertSame(1, Overtime::count());
    }

    public function test_leave_application_counts_chargeable_days_using_configured_rules(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Leave');
        $this->grantPermissions($company, ['create-leave-applications']);

        LeaveType::create([
            'name' => 'Annual Leave',
            'description' => 'Annual leave',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'working_days', 'created_by' => $company->id],
            ['value' => json_encode([1, 2, 3, 4, 5]), 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_count_non_working_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_count_holidays', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );

        Holiday::create([
            'name' => 'Dia Feriado',
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
            'holiday_type_id' => null,
            'description' => 'Teste',
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $leaveTypeId = LeaveType::query()->value('id');

        $response = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveTypeId,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-10',
            'reason' => 'Descanso anual',
            'attachment' => '',
        ]);

        $response->assertRedirect(route('hrm.leave-applications.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame(1, LeaveApplication::count());
        $this->assertSame(4, (int) LeaveApplication::query()->first()->total_days);
    }

    public function test_maternity_leave_requires_reference_date_and_respects_legal_window(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Maternidade');
        $this->grantPermissions($company, ['create-leave-applications']);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Licença Maternidade',
            'legal_code' => 'maternity',
            'description' => 'Regra legal MZ',
            'max_days_per_year' => 90,
            'is_paid' => true,
            'must_be_consecutive' => true,
            'fixed_duration_days' => 90,
            'pre_event_start_window_days' => 20,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingReference = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-29',
            'reason' => 'Licença maternidade',
            'attachment' => '',
        ]);
        $missingReference->assertSessionHasErrors('legal_reference_date');

        $invalidWindow = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-07-29',
            'legal_reference_date' => '2026-06-10',
            'reason' => 'Licença maternidade',
            'attachment' => '',
        ]);
        $invalidWindow->assertSessionHasErrors('start_date');

        $valid = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-25',
            'end_date' => '2026-08-22',
            'legal_reference_date' => '2026-06-10',
            'reason' => 'Licença maternidade',
            'attachment' => '',
        ]);

        $valid->assertRedirect(route('hrm.leave-applications.index'));
        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'legal_reference_date' => '2026-06-10 00:00:00',
            'total_days' => 90,
            'effective_rest_days' => 90,
            'created_by' => $company->id,
        ]);
    }

    public function test_paternity_leave_must_start_after_birth_and_have_fixed_duration(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Paternidade');
        $this->grantPermissions($company, ['create-leave-applications']);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Licença Paternidade',
            'legal_code' => 'paternity',
            'description' => 'Regra legal MZ',
            'max_days_per_year' => 7,
            'is_paid' => true,
            'must_be_consecutive' => true,
            'fixed_duration_days' => 7,
            'post_event_start_offset_days' => 1,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invalidStart = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-16',
            'legal_reference_date' => '2026-06-10',
            'reason' => 'Licença paternidade',
            'attachment' => '',
        ]);
        $invalidStart->assertSessionHasErrors('start_date');

        $valid = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-11',
            'end_date' => '2026-06-17',
            'legal_reference_date' => '2026-06-10',
            'reason' => 'Licença paternidade',
            'attachment' => '',
        ]);

        $valid->assertRedirect(route('hrm.leave-applications.index'));
        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'total_days' => 7,
            'legal_reference_date' => '2026-06-10 00:00:00',
            'created_by' => $company->id,
        ]);
    }

    public function test_sick_leave_requires_attachment_when_configured(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Baixa');
        $this->grantPermissions($company, ['create-leave-applications']);

        $leaveType = LeaveType::query()->create([
            'name' => 'Baixa Médica',
            'legal_code' => 'sick_leave',
            'description' => 'Baixa',
            'max_days_per_year' => 30,
            'is_paid' => false,
            'requires_supporting_document' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingAttachment = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'reason' => 'Doença',
            'attachment' => '',
        ]);
        $missingAttachment->assertSessionHasErrors('attachment');

        $valid = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'reason' => 'Doença',
            'attachment' => 'atestado-medico.pdf',
        ]);
        $valid->assertRedirect(route('hrm.leave-applications.index'));
    }

    public function test_leave_cash_out_respects_minimum_effective_rest_days(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Férias');
        $this->grantPermissions($company, ['create-leave-applications']);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Férias Anuais',
            'legal_code' => 'annual',
            'description' => 'Férias',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'allow_cash_out' => true,
            'min_effective_rest_days' => 6,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invalidCompensation = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-12',
            'compensated_days' => 5,
            'reason' => 'Férias anuais',
            'attachment' => '',
        ]);
        $invalidCompensation->assertSessionHasErrors('compensated_days');

        $validCompensation = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-16',
            'compensated_days' => 4,
            'reason' => 'Férias anuais',
            'attachment' => '',
        ]);
        $validCompensation->assertRedirect(route('hrm.leave-applications.index'));

        $this->assertDatabaseHas('leave_applications', [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'compensated_days' => 4,
            'effective_rest_days' => 6,
            'created_by' => $company->id,
        ]);
    }

    private function settingValue(string $key, int $companyId): ?string
    {
        return Setting::query()
            ->where('key', $key)
            ->where('created_by', $companyId)
            ->value('value');
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
