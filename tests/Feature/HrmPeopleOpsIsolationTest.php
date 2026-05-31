<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Award;
use Workdo\Hrm\Models\AwardType;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\EmployeeTransfer;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Promotion;
use Workdo\Hrm\Models\Resignation;

class HrmPeopleOpsIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_award_store_rejects_cross_company_employee_and_award_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-awards']);

        $employeeB = $this->makeStaffUser($companyB, 'Award External Employee');
        $awardTypeB = AwardType::query()->create([
            'name' => 'Outstanding Performance B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.awards.store'), [
            'employee_id' => $employeeB->id,
            'award_type_id' => $awardTypeB->id,
            'award_date' => '2026-05-28',
            'description' => 'Cross-company attempt',
            'certificate' => null,
        ]);

        $response->assertSessionHasErrors(['employee_id', 'award_type_id']);
        $this->assertDatabaseMissing('awards', [
            'employee_id' => $employeeB->id,
            'award_type_id' => $awardTypeB->id,
            'created_by' => $companyA->id,
        ]);
    }

    public function test_promotion_store_rejects_cross_company_employee_and_structure_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-promotions']);

        $employeeB = $this->makeStaffUser($companyB, 'Promotion External Employee');
        [$branchB, $departmentB, $designationB] = $this->makeOrgStructure($companyB, 'B');

        $response = $this->actingAs($companyA)->post(route('hrm.promotions.store'), [
            'employee_id' => $employeeB->id,
            'current_branch_id' => $branchB->id,
            'current_department_id' => $departmentB->id,
            'current_designation_id' => $designationB->id,
            'effective_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Cross-company attempt',
            'document' => null,
        ]);

        $response->assertSessionHasErrors(['employee_id', 'current_branch_id', 'current_department_id', 'current_designation_id']);
        $this->assertDatabaseMissing('promotions', [
            'employee_id' => $employeeB->id,
            'current_branch_id' => $branchB->id,
            'created_by' => $companyA->id,
        ]);
    }

    public function test_leave_application_store_rejects_cross_company_employee_and_leave_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-leave-applications']);

        Setting::updateOrCreate(
            ['key' => 'mz_leave_min_notice_days', 'created_by' => $companyA->id],
            ['value' => '0', 'is_public' => 1]
        );

        $employeeB = $this->makeStaffUser($companyB, 'Leave External Employee');
        $leaveTypeB = LeaveType::query()->create([
            'name' => 'Annual Leave B',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.leave-applications.store'), [
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveTypeB->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'reason' => 'Cross-company attempt',
            'attachment' => null,
        ]);

        $response->assertSessionHasErrors(['employee_id', 'leave_type_id']);
        $this->assertDatabaseMissing('leave_applications', [
            'employee_id' => $employeeB->id,
            'leave_type_id' => $leaveTypeB->id,
            'created_by' => $companyA->id,
        ]);
    }

    public function test_employee_transfer_store_rejects_cross_company_employee_and_structure_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-employee-transfers']);

        $employeeB = $this->makeStaffUser($companyB, 'Transfer External Employee');
        [$branchB, $departmentB, $designationB] = $this->makeOrgStructure($companyB, 'TR-B');

        $response = $this->actingAs($companyA)->post(route('hrm.employee-transfers.store'), [
            'employee_id' => $employeeB->id,
            'to_branch_id' => $branchB->id,
            'to_department_id' => $departmentB->id,
            'to_designation_id' => $designationB->id,
            'effective_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Cross-company attempt',
            'document' => null,
        ]);

        $response->assertSessionHasErrors(['employee_id', 'to_branch_id', 'to_department_id', 'to_designation_id']);
        $this->assertDatabaseMissing('employee_transfers', [
            'employee_id' => $employeeB->id,
            'to_branch_id' => $branchB->id,
            'created_by' => $companyA->id,
        ]);
    }

    public function test_resignation_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-resignations']);

        $employeeA = $this->makeStaffUser($companyA, 'Resignation Internal Employee');
        $employeeB = $this->makeStaffUser($companyB, 'Resignation External Employee');

        $resignation = Resignation::query()->create([
            'employee_id' => $employeeB->id,
            'last_working_date' => '2026-06-30',
            'reason' => 'External base',
            'description' => null,
            'status' => 'pending',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.resignations.update', $resignation->id), [
            'employee_id' => $employeeA->id,
            'last_working_date' => '2026-07-15',
            'reason' => 'Invalid update attempt',
            'description' => 'Should be blocked',
            'document' => null,
        ]);

        $response->assertRedirect(route('hrm.resignations.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('resignations', [
            'id' => $resignation->id,
            'reason' => 'External base',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_promotion_status_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['manage-promotions-status']);

        $employeeB = $this->makeStaffUser($companyB, 'Promotion External Status Employee');
        [$branchB, $departmentB, $designationB] = $this->makeOrgStructure($companyB, 'STS-B');

        $promotion = Promotion::query()->create([
            'employee_id' => $employeeB->id,
            'previous_branch_id' => $branchB->id,
            'previous_department_id' => $departmentB->id,
            'previous_designation_id' => $designationB->id,
            'current_branch_id' => $branchB->id,
            'current_department_id' => $departmentB->id,
            'current_designation_id' => $designationB->id,
            'effective_date' => now()->addDays(7)->toDateString(),
            'reason' => 'External promotion',
            'status' => 'pending',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.promotions.update-status', $promotion->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('hrm.promotions.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'status' => 'pending',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_award_destroy_requires_manage_scope_for_same_company_records(): void
    {
        $company = $this->makeCompany();
        $manager = $this->makeStaffUser($company, 'Award Manager');
        $owner = $this->makeStaffUser($company, 'Award Owner');

        $this->grantPermissions($manager, ['delete-awards']);

        $awardType = AwardType::query()->create([
            'name' => 'Award Scope Test',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $award = Award::query()->create([
            'employee_id' => $owner->id,
            'award_type_id' => $awardType->id,
            'award_date' => now()->toDateString(),
            'description' => 'Scope test',
            'creator_id' => $owner->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($manager)->delete(route('hrm.awards.destroy', $award->id));

        $response->assertRedirect(route('hrm.awards.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('awards', [
            'id' => $award->id,
            'created_by' => $company->id,
        ]);
    }

    public function test_employee_transfer_destroy_requires_manage_scope_for_same_company_records(): void
    {
        $company = $this->makeCompany();
        $manager = $this->makeStaffUser($company, 'Transfer Manager');
        $owner = $this->makeStaffUser($company, 'Transfer Owner');

        $this->grantPermissions($manager, ['delete-employee-transfers']);
        [$branch, $department, $designation] = $this->makeOrgStructure($company, 'TR-SCOPE');

        $transfer = EmployeeTransfer::query()->create([
            'employee_id' => $owner->id,
            'from_branch_id' => $branch->id,
            'from_department_id' => $department->id,
            'from_designation_id' => $designation->id,
            'to_branch_id' => $branch->id,
            'to_department_id' => $department->id,
            'to_designation_id' => $designation->id,
            'effective_date' => now()->addDay()->toDateString(),
            'reason' => 'Scope test',
            'status' => 'pending',
            'creator_id' => $owner->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($manager)->delete(route('hrm.employee-transfers.destroy', $transfer->id));

        $response->assertRedirect(route('hrm.employee-transfers.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employee_transfers', [
            'id' => $transfer->id,
            'created_by' => $company->id,
        ]);
    }

    public function test_promotion_destroy_requires_manage_scope_for_same_company_records(): void
    {
        $company = $this->makeCompany();
        $manager = $this->makeStaffUser($company, 'Promotion Manager');
        $owner = $this->makeStaffUser($company, 'Promotion Owner');

        $this->grantPermissions($manager, ['delete-promotions']);
        [$branch, $department, $designation] = $this->makeOrgStructure($company, 'PR-SCOPE');

        $promotion = Promotion::query()->create([
            'employee_id' => $owner->id,
            'previous_branch_id' => $branch->id,
            'previous_department_id' => $department->id,
            'previous_designation_id' => $designation->id,
            'current_branch_id' => $branch->id,
            'current_department_id' => $department->id,
            'current_designation_id' => $designation->id,
            'effective_date' => now()->addDays(3)->toDateString(),
            'reason' => 'Scope test',
            'status' => 'pending',
            'creator_id' => $owner->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($manager)->delete(route('hrm.promotions.destroy', $promotion->id));

        $response->assertRedirect(route('hrm.promotions.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'created_by' => $company->id,
        ]);
    }

    public function test_resignation_destroy_requires_manage_scope_for_same_company_records(): void
    {
        $company = $this->makeCompany();
        $manager = $this->makeStaffUser($company, 'Resignation Manager');
        $owner = $this->makeStaffUser($company, 'Resignation Owner');

        $this->grantPermissions($manager, ['delete-resignations']);

        $resignation = Resignation::query()->create([
            'employee_id' => $owner->id,
            'last_working_date' => now()->addMonth()->toDateString(),
            'reason' => 'Scope test',
            'description' => null,
            'status' => 'pending',
            'creator_id' => $owner->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($manager)->delete(route('hrm.resignations.destroy', $resignation->id));

        $response->assertRedirect(route('hrm.resignations.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('resignations', [
            'id' => $resignation->id,
            'created_by' => $company->id,
        ]);
    }

    public function test_leave_application_destroy_requires_manage_scope_for_same_company_records(): void
    {
        $company = $this->makeCompany();
        $manager = $this->makeStaffUser($company, 'Leave Manager');
        $owner = $this->makeStaffUser($company, 'Leave Owner');

        $this->grantPermissions($manager, ['delete-leave-applications']);

        $leaveType = LeaveType::query()->create([
            'name' => 'Leave Scope Test',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $leaveApplication = LeaveApplication::query()->create([
            'employee_id' => $owner->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'total_days' => 2,
            'reason' => 'Scope test',
            'status' => 'pending',
            'creator_id' => $owner->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($manager)->delete(route('hrm.leave-applications.destroy', $leaveApplication->id));

        $response->assertRedirect(route('hrm.leave-applications.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('leave_applications', [
            'id' => $leaveApplication->id,
            'created_by' => $company->id,
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

    private function makeStaffUser(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeOrgStructure(User $company, string $suffix): array
    {
        $branch = Branch::query()->create([
            'branch_name' => 'Branch ' . $suffix,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $department = Department::query()->create([
            'department_name' => 'Department ' . $suffix,
            'branch_id' => $branch->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $designation = Designation::query()->create([
            'designation_name' => 'Designation ' . $suffix,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$branch, $department, $designation];
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
