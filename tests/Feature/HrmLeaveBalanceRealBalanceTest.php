<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Shift;

class HrmLeaveBalanceRealBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    public function test_leave_balance_page_shows_real_balance_with_pending_leave_and_unjustified_absence_penalty(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-leave-balance', 'manage-any-leave-balance']);

        $employeeUser = $this->makeEmployeeUser($company, 'Leave Balance Worker');
        Employee::query()->create([
            'employee_id' => 'EMP-LB-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'date_of_joining' => '2024-01-10',
            'basic_salary' => 35000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $shift = Shift::query()->create([
            'shift_name' => 'Day Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'break_start_time' => null,
            'break_end_time' => null,
            'is_night_shift' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $annualLeaveType = LeaveType::query()->create([
            'name' => 'Férias Anuais',
            'legal_code' => 'annual',
            'description' => 'Férias anuais',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'allow_cash_out' => true,
            'color' => '#22c55e',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->setPolicy($company->id, 'mz_leave_unjustified_absence_penalty_per_day', '1');

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $annualLeaveType->id,
            'start_date' => '2026-03-03',
            'end_date' => '2026-03-10',
            'total_days' => 8,
            'compensated_days' => 0,
            'effective_rest_days' => 8,
            'reason' => 'Férias aprovadas',
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
            'reason' => 'Férias planeadas',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        foreach (['2026-05-05', '2026-05-06'] as $absenceDate) {
            Attendance::query()->create([
                'employee_id' => $employeeUser->id,
                'shift_id' => $shift->id,
                'date' => $absenceDate,
                'clock_in' => Carbon::parse($absenceDate . ' 08:00:00'),
                'clock_out' => Carbon::parse($absenceDate . ' 17:00:00'),
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
        }

        $response = $this->actingAs($company)->get(route('hrm.leave-balance.index'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('Hrm/LeaveBalance/Index')
                ->where('leaveBalances.0.employee_name', 'Leave Balance Worker')
                ->where('leaveBalances.0.leave_types.0.total_days', 28)
                ->where('leaveBalances.0.leave_types.0.base_entitlement_days', 30)
                ->where('leaveBalances.0.leave_types.0.absence_penalty_days', 2)
                ->where('leaveBalances.0.leave_types.0.unjustified_absence_days', 2)
                ->where('leaveBalances.0.leave_types.0.approved_days', 8)
                ->where('leaveBalances.0.leave_types.0.pending_days', 5)
                ->where('leaveBalances.0.leave_types.0.used_days', 13)
                ->where('leaveBalances.0.leave_types.0.available_days', 15)
                ->where('leaveBalances.0.leave_types.0.service_year_index', 3);
        });
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

    private function setPolicy(int $companyId, string $key, string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key, 'created_by' => $companyId],
            ['value' => $value, 'is_public' => 1]
        );

        Cache::forget('company_settings_' . $companyId);
        Cache::forget('company_settings_' . $companyId . '_public');
        Cache::forget('company_settings_owner:' . $companyId);
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
