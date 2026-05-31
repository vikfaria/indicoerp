<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Shift;

class HrmOrgStructureIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_department_store_rejects_cross_company_branch_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-departments']);

        $branchB = $this->makeBranch($companyB, 'Branch B');

        $response = $this->actingAs($companyA)->post(route('hrm.departments.store'), [
            'department_name' => 'Department A Attempt',
            'branch_id' => $branchB->id,
        ]);

        $response->assertSessionHasErrors(['branch_id']);
        $this->assertDatabaseMissing('departments', [
            'department_name' => 'Department A Attempt',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_designation_store_rejects_cross_company_branch_and_department_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-designations']);

        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');

        $response = $this->actingAs($companyA)->post(route('hrm.designations.store'), [
            'designation_name' => 'Designation A Attempt',
            'branch_id' => $branchB->id,
            'department_id' => $departmentB->id,
        ]);

        $response->assertSessionHasErrors(['branch_id', 'department_id']);
        $this->assertDatabaseMissing('designations', [
            'designation_name' => 'Designation A Attempt',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_designation_store_rejects_department_branch_mismatch_within_company(): void
    {
        $companyA = $this->makeCompany();
        $this->grantPermissions($companyA, ['create-designations']);

        $branch1 = $this->makeBranch($companyA, 'Branch A1');
        $branch2 = $this->makeBranch($companyA, 'Branch A2');
        $departmentBranch1 = $this->makeDepartment($companyA, $branch1, 'Department A1');

        $response = $this->actingAs($companyA)->post(route('hrm.designations.store'), [
            'designation_name' => 'Designation Mismatch Attempt',
            'branch_id' => $branch2->id,
            'department_id' => $departmentBranch1->id,
        ]);

        $response->assertSessionHasErrors(['department_id']);
        $this->assertDatabaseMissing('designations', [
            'designation_name' => 'Designation Mismatch Attempt',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_branch_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-branches']);

        $branchB = $this->makeBranch($companyB, 'Branch B');

        $response = $this->actingAs($companyA)->put(route('hrm.branches.update', $branchB->id), [
            'branch_name' => 'Updated by A',
        ]);

        $response->assertRedirect(route('hrm.branches.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('branches', [
            'id' => $branchB->id,
            'branch_name' => 'Branch B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_department_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-departments']);

        $branchA = $this->makeBranch($companyA, 'Branch A');
        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');

        $response = $this->actingAs($companyA)->put(route('hrm.departments.update', $departmentB->id), [
            'department_name' => 'Updated by A',
            'branch_id' => $branchA->id,
        ]);

        $response->assertRedirect(route('hrm.departments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', [
            'id' => $departmentB->id,
            'department_name' => 'Department B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_designation_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-designations']);

        $branchA = $this->makeBranch($companyA, 'Branch A');
        $departmentA = $this->makeDepartment($companyA, $branchA, 'Department A');

        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');
        $designationB = $this->makeDesignation($companyB, $branchB, $departmentB, 'Designation B');

        $response = $this->actingAs($companyA)->put(route('hrm.designations.update', $designationB->id), [
            'designation_name' => 'Updated by A',
            'branch_id' => $branchA->id,
            'department_id' => $departmentA->id,
        ]);

        $response->assertRedirect(route('hrm.designations.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('designations', [
            'id' => $designationB->id,
            'designation_name' => 'Designation B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_shift_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['edit-shifts']);

        $shiftB = $this->makeShift($companyB, 'Shift B');

        $response = $this->actingAs($companyA)->put(route('hrm.shifts.update', $shiftB->id), [
            'shift_name' => 'Updated by A',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'break_start_time' => '13:00',
            'break_end_time' => '14:00',
            'is_night_shift' => false,
        ]);

        $response->assertRedirect(route('hrm.shifts.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('shifts', [
            'id' => $shiftB->id,
            'shift_name' => 'Shift B',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_branch_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['delete-branches']);

        $branchB = $this->makeBranch($companyB, 'Branch B');

        $response = $this->actingAs($companyA)->delete(route('hrm.branches.destroy', $branchB->id));

        $response->assertRedirect(route('hrm.branches.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('branches', [
            'id' => $branchB->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_department_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['delete-departments']);

        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');

        $response = $this->actingAs($companyA)->delete(route('hrm.departments.destroy', $departmentB->id));

        $response->assertRedirect(route('hrm.departments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', [
            'id' => $departmentB->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_designation_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['delete-designations']);

        $branchB = $this->makeBranch($companyB, 'Branch B');
        $departmentB = $this->makeDepartment($companyB, $branchB, 'Department B');
        $designationB = $this->makeDesignation($companyB, $branchB, $departmentB, 'Designation B');

        $response = $this->actingAs($companyA)->delete(route('hrm.designations.destroy', $designationB->id));

        $response->assertRedirect(route('hrm.designations.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('designations', [
            'id' => $designationB->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_shift_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();
        $this->grantPermissions($companyA, ['delete-shifts']);

        $shiftB = $this->makeShift($companyB, 'Shift B');

        $response = $this->actingAs($companyA)->delete(route('hrm.shifts.destroy', $shiftB->id));

        $response->assertRedirect(route('hrm.shifts.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('shifts', [
            'id' => $shiftB->id,
            'created_by' => $companyB->id,
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

    private function makeBranch(User $company, string $name): Branch
    {
        return Branch::query()->create([
            'branch_name' => $name,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeDepartment(User $company, Branch $branch, string $name): Department
    {
        return Department::query()->create([
            'department_name' => $name,
            'branch_id' => $branch->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeDesignation(User $company, Branch $branch, Department $department, string $name): Designation
    {
        return Designation::query()->create([
            'designation_name' => $name,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeShift(User $company, string $name): Shift
    {
        return Shift::query()->create([
            'shift_name' => $name,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
            'is_night_shift' => false,
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
