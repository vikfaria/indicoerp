<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Shift;

class HrmLeaveAttendanceCancellationControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_attendance_cancellation_requires_reason(): void
    {
        [$company, $attendance] = $this->makeAttendanceScenario();
        $this->grantPermissions($company, ['delete-attendances', 'manage-any-attendances']);

        $response = $this->actingAs($company)->delete(route('hrm.attendances.destroy', $attendance->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'is_cancelled' => 0,
        ]);
    }

    public function test_attendance_delete_route_cancels_instead_of_deleting(): void
    {
        [$company, $attendance] = $this->makeAttendanceScenario();
        $this->grantPermissions($company, ['delete-attendances', 'manage-any-attendances']);

        $reason = 'Registo duplicado de ponto detectado na auditoria interna.';

        $response = $this->actingAs($company)->delete(route('hrm.attendances.destroy', $attendance->id), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'is_cancelled' => 1,
            'cancellation_reason' => $reason,
            'cancelled_by' => $company->id,
        ]);
    }

    public function test_leave_application_cancellation_requires_reason(): void
    {
        [$company, $leaveApplication] = $this->makeLeaveScenario();
        $this->grantPermissions($company, ['delete-leave-applications', 'manage-any-leave-applications']);

        $response = $this->actingAs($company)->delete(route('hrm.leave-applications.destroy', $leaveApplication->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leaveApplication->id,
            'is_cancelled' => 0,
        ]);
    }

    public function test_leave_application_delete_route_cancels_instead_of_deleting(): void
    {
        [$company, $leaveApplication] = $this->makeLeaveScenario();
        $this->grantPermissions($company, ['delete-leave-applications', 'manage-any-leave-applications']);

        $reason = 'Pedido submetido em período errado e será recriado corretamente.';

        $response = $this->actingAs($company)->delete(route('hrm.leave-applications.destroy', $leaveApplication->id), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_applications', [
            'id' => $leaveApplication->id,
            'is_cancelled' => 1,
            'cancellation_reason' => $reason,
            'cancelled_by' => $company->id,
        ]);
    }

    private function makeAttendanceScenario(): array
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaffUser($company, 'Attendance Staff');
        $shift = Shift::query()->create([
            'shift_name' => 'Regular Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $attendance = Attendance::query()->create([
            'employee_id' => $staff->id,
            'shift_id' => $shift->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->copy()->setTime(8, 0, 0),
            'clock_out' => now()->copy()->setTime(17, 0, 0),
            'break_hour' => 1,
            'total_hour' => 8,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'status' => 'present',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$company, $attendance];
    }

    private function makeLeaveScenario(): array
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaffUser($company, 'Leave Staff');
        $leaveType = LeaveType::query()->create([
            'name' => 'Annual Leave',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $leaveApplication = LeaveApplication::query()->create([
            'employee_id' => $staff->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'total_days' => 2,
            'reason' => 'Descanso anual',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$company, $leaveApplication];
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

    private function makeStaffUser(User $company, string $name): User
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
