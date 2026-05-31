<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Shift;

class HrmAttendanceIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_attendance_store_rejects_cross_company_employee(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-attendances']);

        $foreignStaff = $this->makeStaffUser($companyB, 'Foreign Worker');
        $foreignShift = $this->makeShift($companyB, 'Foreign Shift');
        $this->attachEmployeeProfile($companyB, $foreignStaff, 'EMP-ATT-FOR-001', $foreignShift);

        $response = $this->actingAs($companyA)->post(route('hrm.attendances.store'), [
            'employee_id' => $foreignStaff->id,
            'date' => '2026-05-27',
            'clock_in' => '2026-05-27 08:00',
            'clock_out' => '2026-05-27 17:00',
            'notes' => 'Tentativa inválida',
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_attendance_update_rejects_cross_company_employee_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-attendances', 'edit-attendances', 'manage-any-attendances']);

        $staffA = $this->makeStaffUser($companyA, 'Internal Worker');
        $shiftA = $this->makeShift($companyA, 'Shift A');
        $this->attachEmployeeProfile($companyA, $staffA, 'EMP-ATT-A-001', $shiftA);

        $this->actingAs($companyA)->post(route('hrm.attendances.store'), [
            'employee_id' => $staffA->id,
            'date' => '2026-05-27',
            'clock_in' => '2026-05-27 08:00',
            'clock_out' => '2026-05-27 17:00',
            'notes' => 'Registo válido',
        ]);

        $attendance = Attendance::query()->latest('id')->firstOrFail();

        $foreignStaff = $this->makeStaffUser($companyB, 'Foreign Worker B');
        $foreignShift = $this->makeShift($companyB, 'Shift B');
        $this->attachEmployeeProfile($companyB, $foreignStaff, 'EMP-ATT-B-001', $foreignShift);

        $response = $this->actingAs($companyA)->put(route('hrm.attendances.update', $attendance->id), [
            'employee_id' => $foreignStaff->id,
            'date' => '2026-05-27',
            'clock_in' => '2026-05-27 08:15',
            'clock_out' => '2026-05-27 17:00',
            'notes' => 'Tentativa indevida',
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'employee_id' => $staffA->id,
            'notes' => 'Registo válido',
        ]);
    }

    public function test_attendance_destroy_rejects_cross_company_record(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-attendances', 'manage-any-attendances']);

        $staffB = $this->makeStaffUser($companyB, 'Worker B');
        $shiftB = $this->makeShift($companyB, 'Shift B');
        $this->attachEmployeeProfile($companyB, $staffB, 'EMP-ATT-B-002', $shiftB);

        $attendance = Attendance::query()->create([
            'employee_id' => $staffB->id,
            'shift_id' => $shiftB->id,
            'date' => '2026-05-27',
            'clock_in' => '2026-05-27 08:00:00',
            'clock_out' => '2026-05-27 17:00:00',
            'status' => 'present',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.attendances.destroy', $attendance->id));

        $response->assertRedirect(route('hrm.attendances.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('attendances', ['id' => $attendance->id]);
    }

    public function test_manage_own_attendance_index_does_not_leak_foreign_company_rows(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $staffA = $this->makeStaffUser($companyA, 'Own Attendance User');
        $this->grantPermissions($staffA, ['manage-attendances', 'manage-own-attendances']);

        $shiftA = $this->makeShift($companyA, 'Own Shift Visible');
        $this->attachEmployeeProfile($companyA, $staffA, 'EMP-ATT-A-INDEX', $shiftA);

        Attendance::query()->create([
            'employee_id' => $staffA->id,
            'shift_id' => $shiftA->id,
            'date' => '2026-05-27',
            'clock_in' => '2026-05-27 08:00:00',
            'clock_out' => '2026-05-27 17:00:00',
            'status' => 'present',
            'creator_id' => $staffA->id,
            'created_by' => $companyA->id,
        ]);

        $shiftB = $this->makeShift($companyB, 'Foreign Shift Must Hide');
        Attendance::query()->create([
            'employee_id' => $staffA->id,
            'shift_id' => $shiftB->id,
            'date' => '2026-05-28',
            'clock_in' => '2026-05-28 08:00:00',
            'clock_out' => '2026-05-28 17:00:00',
            'status' => 'present',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($staffA)->get(route('hrm.attendances.index'));

        $response->assertOk();
        $response->assertSee('Own Shift Visible', false);
        $response->assertDontSee('Foreign Shift Must Hide', false);
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

    private function makeShift(User $company, string $name): Shift
    {
        return Shift::query()->create([
            'shift_name' => $name,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
            'is_night_shift' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function attachEmployeeProfile(User $company, User $staff, string $employeeCode, Shift $shift): Employee
    {
        return Employee::query()->create([
            'employee_id' => $employeeCode,
            'user_id' => $staff->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400000002',
            'basic_salary' => 9000,
            'shift' => $shift->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
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
