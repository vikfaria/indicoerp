<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\Attendance;

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

    public function test_adoption_leave_requires_legal_reference_date_and_cannot_start_before_event(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Adocao');
        $this->grantPermissions($company, ['create-leave-applications']);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Licença de Adoção',
            'legal_code' => 'adoption',
            'description' => 'Dispensa por adoção',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'requires_supporting_document' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingReferenceDate = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
            'reason' => 'Adoção',
            'attachment' => 'comprovativo-adocao.pdf',
        ]);
        $missingReferenceDate->assertSessionHasErrors('legal_reference_date');

        $invalidStartDate = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
            'legal_reference_date' => '2026-10-12',
            'reason' => 'Adoção',
            'attachment' => 'comprovativo-adocao.pdf',
        ]);
        $invalidStartDate->assertSessionHasErrors('start_date');

        $validRequest = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-17',
            'legal_reference_date' => '2026-10-12',
            'reason' => 'Adoção',
            'attachment' => 'comprovativo-adocao.pdf',
        ]);
        $validRequest->assertRedirect(route('hrm.leave-applications.index'));
        $validRequest->assertSessionHasNoErrors();
    }

    public function test_cash_compensation_is_blocked_for_non_annual_leave_types(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Compensacao Nao Anual');
        $this->grantPermissions($company, ['create-leave-applications']);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Licença Médica Compensável',
            'legal_code' => 'sick_leave',
            'description' => 'Teste de bloqueio de compensação',
            'max_days_per_year' => 20,
            'is_paid' => false,
            'allow_cash_out' => true,
            'requires_supporting_document' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-03',
            'end_date' => '2026-11-07',
            'compensated_days' => 1,
            'reason' => 'Teste compensação',
            'attachment' => 'atestado.pdf',
        ]);

        $response->assertSessionHasErrors('compensated_days');
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

    public function test_annual_leave_entitlement_is_prorated_in_first_service_year(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Primeiro Ano');
        $this->grantPermissions($company, ['create-leave-applications', 'view-leave-applications']);

        Employee::query()->create([
            'employee_id' => 'LEAVE-Y1-001',
            'user_id' => $employeeUser->id,
            'date_of_joining' => '2026-07-15',
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $company->id],
            ['value' => '0', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_entitlement_first_year_days', 'created_by' => $company->id],
            ['value' => '12', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_entitlement_following_year_days', 'created_by' => $company->id],
            ['value' => '30', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_entitlement_prorate_first_year', 'created_by' => $company->id],
            ['value' => '1', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Ferias Anuais',
            'legal_code' => 'annual',
            'description' => 'Ferias anuais',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $balanceResponse = $this->actingAs($company)->get(route('hrm.leave-balance', [$employeeUser->id, $leaveType->id]));
        $balanceResponse->assertOk()
            ->assertJsonPath('total_leaves', 6)
            ->assertJsonPath('service_year_index', 1);

        $firstRequest = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-08',
            'reason' => 'Ferias no primeiro ano',
            'attachment' => '',
        ]);
        $firstRequest->assertRedirect(route('hrm.leave-applications.index'));
        $firstRequest->assertSessionHasNoErrors();

        $exceedingRequest = $this->actingAs($company)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-07',
            'reason' => 'Excede saldo primeiro ano',
            'attachment' => '',
        ]);
        $exceedingRequest->assertSessionHasErrors('start_date');
    }

    public function test_unjustified_absence_penalty_reduces_annual_leave_entitlement(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Penalizacao Ferias');
        $this->grantPermissions($company, ['view-leave-applications']);

        $shift = Shift::query()->create([
            'shift_name' => 'Turno Padrão',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'LEAVE-ABS-001',
            'user_id' => $employeeUser->id,
            'date_of_joining' => '2024-01-10',
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_entitlement_following_year_days', 'created_by' => $company->id],
            ['value' => '30', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_unjustified_absence_penalty_per_day', 'created_by' => $company->id],
            ['value' => '1', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Ferias Anuais Penalizacao',
            'legal_code' => 'annual',
            'description' => 'Ferias anuais',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        foreach (['2026-05-05', '2026-05-06'] as $absenceDate) {
            $clockIn = Carbon::parse($absenceDate . ' 08:00:00');
            $clockOut = Carbon::parse($absenceDate . ' 17:00:00');

            Attendance::query()->create([
                'employee_id' => $employeeUser->id,
                'shift_id' => $shift->id,
                'date' => $absenceDate,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_hour' => 1,
                'total_hour' => 0,
                'overtime_hours' => 0,
                'overtime_amount' => 0,
                'status' => 'absent',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
        }

        $balanceResponse = $this->actingAs($company)->get(route('hrm.leave-balance', [$employeeUser->id, $leaveType->id]));
        $balanceResponse->assertOk()
            ->assertJsonPath('base_entitlement_days', 30)
            ->assertJsonPath('unjustified_absence_days', 2)
            ->assertJsonPath('absence_penalty_days', 2)
            ->assertJsonPath('total_leaves', 28);
    }

    public function test_justified_absence_records_are_not_counted_for_annual_leave_penalty(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Ausencia Justificada');
        $this->grantPermissions($company, ['view-leave-applications']);

        $shift = Shift::query()->create([
            'shift_name' => 'Turno Padrão',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'LEAVE-ABS-002',
            'user_id' => $employeeUser->id,
            'date_of_joining' => '2024-01-10',
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_entitlement_following_year_days', 'created_by' => $company->id],
            ['value' => '30', 'is_public' => 1]
        );
        Setting::updateOrCreate(
            ['key' => 'mz_leave_unjustified_absence_penalty_per_day', 'created_by' => $company->id],
            ['value' => '1', 'is_public' => 1]
        );

        $leaveType = LeaveType::query()->create([
            'name' => 'Ferias Anuais Penalizacao 2',
            'legal_code' => 'annual',
            'description' => 'Ferias anuais',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $shift->id,
            'date' => '2026-05-05',
            'clock_in' => Carbon::parse('2026-05-05 08:00:00'),
            'clock_out' => Carbon::parse('2026-05-05 17:00:00'),
            'break_hour' => 1,
            'total_hour' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'absent',
            'is_justified' => true,
            'absence_category' => 'medical',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $shift->id,
            'date' => '2026-05-06',
            'clock_in' => Carbon::parse('2026-05-06 08:00:00'),
            'clock_out' => Carbon::parse('2026-05-06 17:00:00'),
            'break_hour' => 1,
            'total_hour' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'absent',
            'is_justified' => false,
            'absence_category' => 'unjustified',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $balanceResponse = $this->actingAs($company)->get(route('hrm.leave-balance', [$employeeUser->id, $leaveType->id]));
        $balanceResponse->assertOk()
            ->assertJsonPath('unjustified_absence_days', 1)
            ->assertJsonPath('absence_penalty_days', 1)
            ->assertJsonPath('total_leaves', 29);
    }

    public function test_attendance_store_defaults_absence_classification_to_unjustified(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Classificacao Falta');
        $this->grantPermissions($company, ['create-attendances']);

        $shift = Shift::query()->create([
            'shift_name' => 'Turno Classificacao',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'AT-RF025-001',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'working_days', 'created_by' => $company->id],
            ['value' => json_encode([1, 2, 3, 4, 5]), 'is_public' => 1]
        );

        $response = $this->actingAs($company)->post(route('hrm.attendances.store'), [
            'employee_id' => $employeeUser->id,
            'date' => '2026-07-01',
            'clock_in' => '2026-07-01 08:00',
            'clock_out' => '2026-07-01 08:00',
            'notes' => 'Registo de ausência',
        ]);

        $response->assertRedirect(route('hrm.attendances.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employeeUser->id,
            'date' => '2026-07-01 00:00:00',
            'status' => 'absent',
            'is_justified' => 0,
            'absence_category' => 'unjustified',
            'created_by' => $company->id,
        ]);
    }

    public function test_attendance_store_forces_unjustified_category_when_flagged_as_unjustified(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Classificacao Forcada');
        $this->grantPermissions($company, ['create-attendances']);

        $shift = Shift::query()->create([
            'shift_name' => 'Turno Classificacao 2',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'AT-RF025-002',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Setting::updateOrCreate(
            ['key' => 'working_days', 'created_by' => $company->id],
            ['value' => json_encode([1, 2, 3, 4, 5]), 'is_public' => 1]
        );

        $response = $this->actingAs($company)->post(route('hrm.attendances.store'), [
            'employee_id' => $employeeUser->id,
            'date' => '2026-07-02',
            'clock_in' => '2026-07-02 08:00',
            'clock_out' => '2026-07-02 08:00',
            'is_justified' => false,
            'absence_category' => 'medical',
            'notes' => 'Ausencia nao justificada com categoria inconsistente',
        ]);

        $response->assertRedirect(route('hrm.attendances.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employeeUser->id,
            'date' => '2026-07-02 00:00:00',
            'status' => 'absent',
            'is_justified' => 0,
            'absence_category' => 'unjustified',
            'created_by' => $company->id,
        ]);
    }

    public function test_attendance_store_blocks_seventh_consecutive_work_day_to_preserve_weekly_rest(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Funcionario Descanso Semanal');
        $this->grantPermissions($company, ['create-attendances']);

        Setting::updateOrCreate(
            ['key' => 'working_days', 'created_by' => $company->id],
            ['value' => json_encode([0, 1, 2, 3, 4, 5, 6]), 'is_public' => 1]
        );

        $shift = Shift::query()->create([
            'shift_name' => 'Turno Semanal',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'AT-RF032-001',
            'user_id' => $employeeUser->id,
            'shift' => $shift->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $startDate = Carbon::parse('2026-07-01');
        for ($i = 0; $i < 6; $i++) {
            $workDate = $startDate->copy()->addDays($i);

            Attendance::query()->create([
                'employee_id' => $employeeUser->id,
                'shift_id' => $shift->id,
                'date' => $workDate->toDateString(),
                'clock_in' => $workDate->copy()->setTime(8, 0),
                'clock_out' => $workDate->copy()->setTime(17, 0),
                'total_hour' => 8,
                'break_hour' => 1,
                'overtime_hours' => 0,
                'overtime_amount' => 0,
                'status' => 'present',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
        }

        $seventhDate = $startDate->copy()->addDays(6);
        $response = $this->actingAs($company)->post(route('hrm.attendances.store'), [
            'employee_id' => $employeeUser->id,
            'date' => $seventhDate->toDateString(),
            'clock_in' => $seventhDate->copy()->setTime(8, 0)->format('Y-m-d H:i'),
            'clock_out' => $seventhDate->copy()->setTime(17, 0)->format('Y-m-d H:i'),
            'notes' => 'Attempted seventh consecutive day',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString(
            'Weekly rest rule violated',
            (string) session('error')
        );
        $this->assertSame(6, Attendance::query()->where('created_by', $company->id)->count());
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
