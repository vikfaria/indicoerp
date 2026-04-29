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
            'overtime_monthly_limit_hours' => 40,
            'overtime_yearly_limit_hours' => 200,
            'leave_min_notice_days' => 3,
            'leave_max_consecutive_days' => 30,
            'leave_count_non_working_days' => false,
            'leave_count_holidays' => false,
        ]);

        $response->assertRedirect();

        $this->assertSame('2', (string) $this->settingValue('mz_overtime_daily_limit_hours', $company->id));
        $this->assertSame('40', (string) $this->settingValue('mz_overtime_monthly_limit_hours', $company->id));
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
