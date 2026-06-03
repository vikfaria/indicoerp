<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveType;

class HrmAnnualLeavePlanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_can_create_annual_leave_plan_with_pending_manager_status(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-leave-applications']);

        $employeeUser = $this->makeStaffUser($company, 'Leave Plan Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-001');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave');

        $response = $this->actingAs($company)->post(route('hrm.annual-leave-plans.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-07-01',
            'planned_end_date' => '2026-07-10',
            'notes' => 'Plano anual de ferias',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('annual_leave_plans', [
            'created_by' => $company->id,
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_days' => 10,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
        ]);
    }

    public function test_store_rejects_cross_company_employee_and_leave_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['create-leave-applications']);

        $foreignEmployee = $this->makeStaffUser($companyB, 'Foreign Leave Worker');
        $this->attachEmployeeProfile($companyB, $foreignEmployee, 'EMP-LP-F-001');
        $foreignLeaveType = $this->makeAnnualLeaveType($companyB, 'Annual Leave B');

        $response = $this->actingAs($companyA)->post(route('hrm.annual-leave-plans.store'), [
            'employee_id' => $foreignEmployee->id,
            'leave_type_id' => $foreignLeaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-07-01',
            'planned_end_date' => '2026-07-05',
        ]);

        $response->assertSessionHasErrors(['employee_id', 'leave_type_id']);
        $this->assertDatabaseCount('annual_leave_plans', 0);
    }

    public function test_workflow_moves_from_manager_approval_to_hr_approval(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-leave-applications', 'manage-leave-status']);

        $employeeUser = $this->makeStaffUser($company, 'Workflow Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-W-001');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave WF');

        $plan = AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-08-01',
            'planned_end_date' => '2026-08-10',
            'planned_days' => 10,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $managerResponse = $this->actingAs($company)->put(route('hrm.annual-leave-plans.update-status', $plan->id), [
            'action' => 'manager_approve',
        ]);
        $managerResponse->assertSessionHas('success');
        $this->assertDatabaseHas('annual_leave_plans', [
            'id' => $plan->id,
            'status' => AnnualLeavePlan::STATUS_PENDING_HR,
            'manager_approved_by' => $company->id,
        ]);

        $hrResponse = $this->actingAs($company)->put(route('hrm.annual-leave-plans.update-status', $plan->id), [
            'action' => 'hr_approve',
        ]);
        $hrResponse->assertSessionHas('success');
        $this->assertDatabaseHas('annual_leave_plans', [
            'id' => $plan->id,
            'status' => AnnualLeavePlan::STATUS_APPROVED,
            'hr_approved_by' => $company->id,
        ]);
    }

    public function test_hr_approval_is_blocked_before_manager_approval(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-leave-status']);

        $employeeUser = $this->makeStaffUser($company, 'Workflow Blocked Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-B-001');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave Blocked');

        $plan = AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-09-01',
            'planned_end_date' => '2026-09-06',
            'planned_days' => 6,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(route('hrm.annual-leave-plans.update-status', $plan->id), [
            'action' => 'hr_approve',
        ]);

        $response->assertSessionHasErrors('action');
        $this->assertDatabaseHas('annual_leave_plans', [
            'id' => $plan->id,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'hr_approved_by' => null,
        ]);
    }

    public function test_rejection_requires_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-leave-status']);

        $employeeUser = $this->makeStaffUser($company, 'Reject Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-R-001');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave Reject');

        $plan = AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-10-01',
            'planned_end_date' => '2026-10-05',
            'planned_days' => 5,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(route('hrm.annual-leave-plans.update-status', $plan->id), [
            'action' => 'reject',
        ]);

        $response->assertSessionHasErrors('rejection_reason');
        $this->assertDatabaseHas('annual_leave_plans', [
            'id' => $plan->id,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'rejected_by' => null,
        ]);
    }

    public function test_destroy_requires_cancellation_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['delete-leave-applications']);

        $employeeUser = $this->makeStaffUser($company, 'Cancel Reason Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-C-001');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave Cancel Reason');

        $plan = AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-11-01',
            'planned_end_date' => '2026-11-05',
            'planned_days' => 5,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->delete(route('hrm.annual-leave-plans.destroy', $plan->id));

        $response->assertSessionHasErrors('cancellation_reason');
        $this->assertDatabaseHas('annual_leave_plans', [
            'id' => $plan->id,
            'is_cancelled' => 0,
        ]);
    }

    public function test_destroy_cancels_plan_instead_of_deleting(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['delete-leave-applications']);

        $employeeUser = $this->makeStaffUser($company, 'Cancel Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-C-002');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave Cancel');

        $plan = AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-11-10',
            'planned_end_date' => '2026-11-14',
            'planned_days' => 5,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $reason = 'Plano submetido com datas incorretas.';
        $response = $this->actingAs($company)->delete(route('hrm.annual-leave-plans.destroy', $plan->id), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('annual_leave_plans', [
            'id' => $plan->id,
            'is_cancelled' => 1,
            'cancellation_reason' => $reason,
            'cancelled_by' => $company->id,
        ]);
    }

    public function test_cancelled_plan_does_not_block_new_plan_overlap(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-leave-applications']);

        $employeeUser = $this->makeStaffUser($company, 'Overlap Cancel Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LP-C-003');
        $leaveType = $this->makeAnnualLeaveType($company, 'Annual Leave Overlap');

        AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-12-01',
            'planned_end_date' => '2026-12-05',
            'planned_days' => 5,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $company->id,
            'cancellation_reason' => 'Plano anterior substituido.',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.annual-leave-plans.store'), [
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => 2026,
            'planned_start_date' => '2026-12-01',
            'planned_end_date' => '2026-12-05',
            'notes' => 'Novo plano para substituir o cancelado',
        ]);

        $response->assertSessionHas('success');
        $this->assertSame(
            2,
            AnnualLeavePlan::query()->where('created_by', $company->id)->count()
        );
        $this->assertSame(
            1,
            AnnualLeavePlan::query()->active()->where('created_by', $company->id)->count()
        );
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

    private function attachEmployeeProfile(User $company, User $employeeUser, string $employeeCode): Employee
    {
        return Employee::query()->create([
            'employee_id' => $employeeCode,
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeAnnualLeaveType(User $company, string $name): LeaveType
    {
        return LeaveType::query()->create([
            'name' => $name,
            'description' => 'Annual leave type',
            'legal_code' => 'annual',
            'max_days_per_year' => 30,
            'is_paid' => true,
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
