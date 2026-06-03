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
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDocument;
use Workdo\Hrm\Models\Shift;

class HrmEmployeeAccessIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_employee_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-employees', 'manage-any-employees']);

        [$branchA, $departmentA, $designationA, $shiftA] = $this->makeStructure($companyA, 'A');
        [$branchB, $departmentB, $designationB, $shiftB] = $this->makeStructure($companyB, 'B');

        $employeeB = $this->makeEmployee($companyB, $branchB, $departmentB, $designationB, $shiftB, 'EMP-B-001');

        $response = $this->actingAs($companyA)->put(route('hrm.employees.update', $employeeB->id), $this->employeeUpdatePayload([
            'branch_id' => $branchA->id,
            'department_id' => $departmentA->id,
            'designation_id' => $designationA->id,
            'shift_id' => $shiftA->id,
            'city' => 'Maputo Updated',
        ]));

        $response->assertRedirect(route('hrm.employees.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', [
            'id' => $employeeB->id,
            'city' => 'Maputo',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_employee_update_rejects_cross_company_structure_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-employees', 'manage-any-employees']);

        [$branchA, $departmentA, $designationA, $shiftA] = $this->makeStructure($companyA, 'A');
        [$branchB, $departmentB, $designationB, $shiftB] = $this->makeStructure($companyB, 'B');

        $employeeA = $this->makeEmployee($companyA, $branchA, $departmentA, $designationA, $shiftA, 'EMP-A-001');

        $response = $this->actingAs($companyA)->put(route('hrm.employees.update', $employeeA->id), $this->employeeUpdatePayload([
            'branch_id' => $branchB->id,
            'department_id' => $departmentB->id,
            'designation_id' => $designationB->id,
            'shift_id' => $shiftB->id,
        ]));

        $response->assertSessionHasErrors(['branch_id', 'department_id', 'designation_id', 'shift_id']);
        $this->assertDatabaseHas('employees', [
            'id' => $employeeA->id,
            'branch_id' => $branchA->id,
            'department_id' => $departmentA->id,
            'designation_id' => $designationA->id,
            'shift' => $shiftA->id,
        ]);
    }

    public function test_employee_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-employees', 'manage-any-employees']);

        [$branchB, $departmentB, $designationB, $shiftB] = $this->makeStructure($companyB, 'B');
        $employeeB = $this->makeEmployee($companyB, $branchB, $departmentB, $designationB, $shiftB, 'EMP-B-DEL-001');

        $response = $this->actingAs($companyA)->delete(route('hrm.employees.destroy', $employeeB->id));

        $response->assertRedirect(route('hrm.employees.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', [
            'id' => $employeeB->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_employee_document_delete_denies_cross_company_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-employees', 'manage-any-employees']);

        [$branchB, $departmentB, $designationB, $shiftB] = $this->makeStructure($companyB, 'B');
        $employeeB = $this->makeEmployee($companyB, $branchB, $departmentB, $designationB, $shiftB, 'EMP-B-DOC-001');

        $document = EmployeeDocument::query()->create([
            'user_id' => $employeeB->id,
            'document_type_id' => $this->makeDocumentTypeId($companyB),
            'file_path' => 'employee_documents/sample.pdf',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.employee-documents.destroy', [
            'employeeId' => $employeeB->id,
            'document' => $document->id,
        ]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employee_documents', [
            'id' => $document->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_employee_document_cancellation_requires_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);

        [$branch, $department, $designation, $shift] = $this->makeStructure($company, 'DOC-REQ');
        $employee = $this->makeEmployee($company, $branch, $department, $designation, $shift, 'EMP-DOC-REQ-001');

        $document = EmployeeDocument::query()->create([
            'user_id' => $employee->id,
            'document_type_id' => $this->makeDocumentTypeId($company),
            'file_path' => 'employee_documents/require-reason.pdf',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->delete(route('hrm.employee-documents.destroy', [
            'employeeId' => $employee->id,
            'document' => $document->id,
        ]));

        $response->assertSessionHasErrors('cancellation_reason');
        $this->assertDatabaseHas('employee_documents', [
            'id' => $document->id,
            'is_cancelled' => false,
        ]);
    }

    public function test_employee_document_delete_cancels_document_instead_of_deleting(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);

        [$branch, $department, $designation, $shift] = $this->makeStructure($company, 'DOC-CANCEL');
        $employee = $this->makeEmployee($company, $branch, $department, $designation, $shift, 'EMP-DOC-CANCEL-001');

        $document = EmployeeDocument::query()->create([
            'user_id' => $employee->id,
            'document_type_id' => $this->makeDocumentTypeId($company),
            'file_path' => 'employee_documents/cancel-document.pdf',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->delete(route('hrm.employee-documents.destroy', [
            'employeeId' => $employee->id,
            'document' => $document->id,
        ]), [
            'cancellation_reason' => 'Documento substituido por versao assinada.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employee_documents', [
            'id' => $document->id,
            'is_cancelled' => true,
            'cancelled_by' => $company->id,
        ]);
    }

    public function test_set_salary_update_denies_cross_company_employee_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-set-salary', 'manage-any-set-salary']);

        [$branchB, $departmentB, $designationB, $shiftB] = $this->makeStructure($companyB, 'B');
        $employeeB = $this->makeEmployee($companyB, $branchB, $departmentB, $designationB, $shiftB, 'EMP-B-SAL-001');

        $response = $this->actingAs($companyA)->put(route('hrm.set-salary.update', $employeeB->id), [
            'basic_salary' => 2500,
        ]);

        $response->assertRedirect(route('hrm.set-salary.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', [
            'id' => $employeeB->id,
            'basic_salary' => 1000,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_set_salary_search_does_not_leak_cross_company_employees_for_manage_own_scope(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $staffA = $this->makeStaffUser($companyA, 'Set Salary Staff A');

        $this->grantPermissions($staffA, ['manage-set-salary', 'manage-own-set-salary']);

        [$branchA, $departmentA, $designationA, $shiftA] = $this->makeStructure($companyA, 'A');
        [$branchB, $departmentB, $designationB, $shiftB] = $this->makeStructure($companyB, 'B');

        Employee::query()->create([
            'employee_id' => 'EMP-A-OWN-001',
            'date_of_birth' => '1992-05-10',
            'gender' => 'Male',
            'shift' => $shiftA->id,
            'date_of_joining' => '2024-01-10',
            'employment_type' => 'full_time',
            'address_line_1' => 'Address A',
            'city' => 'Maputo',
            'state' => 'Maputo',
            'country' => 'Mozambique',
            'postal_code' => '1100',
            'emergency_contact_name' => 'Contact A',
            'emergency_contact_relationship' => 'Sibling',
            'emergency_contact_number' => '840000000',
            'bank_name' => 'Bank',
            'account_holder_name' => 'Holder',
            'account_number' => '123456789',
            'bank_identifier_code' => 'BIMOMZMX',
            'bank_branch' => 'Main',
            'tax_payer_id' => '100000001',
            'basic_salary' => 1000,
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'rate_per_hour' => 10,
            'user_id' => $staffA->id,
            'branch_id' => $branchA->id,
            'department_id' => $departmentA->id,
            'designation_id' => $designationA->id,
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $employeeB = $this->makeEmployee($companyB, $branchB, $departmentB, $designationB, $shiftB, 'EMP-B-LEAK-001');

        $response = $this->actingAs($staffA)->get(route('hrm.set-salary.index', [
            'search' => 'EMP-B-LEAK-001',
        ]));

        $response->assertOk();
        $response->assertDontSee($employeeB->user->name);
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

    private function makeStructure(User $company, string $suffix): array
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

        $shift = Shift::query()->create([
            'shift_name' => 'Shift ' . $suffix,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_start_time' => '12:00',
            'break_end_time' => '13:00',
            'is_night_shift' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        return [$branch, $department, $designation, $shift];
    }

    private function makeEmployee(
        User $company,
        Branch $branch,
        Department $department,
        Designation $designation,
        Shift $shift,
        string $employeeCode
    ): Employee {
        $staff = $this->makeStaffUser($company, 'Staff ' . $employeeCode);

        return Employee::query()->create([
            'employee_id' => $employeeCode,
            'date_of_birth' => '1992-05-10',
            'gender' => 'Male',
            'shift' => $shift->id,
            'date_of_joining' => '2024-01-10',
            'employment_type' => 'full_time',
            'address_line_1' => 'Address 1',
            'city' => 'Maputo',
            'state' => 'Maputo',
            'country' => 'Mozambique',
            'postal_code' => '1100',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_relationship' => 'Sibling',
            'emergency_contact_number' => '840000000',
            'bank_name' => 'Bank',
            'account_holder_name' => 'Holder',
            'account_number' => '123456789',
            'bank_identifier_code' => 'BIMOMZMX',
            'bank_branch' => 'Main',
            'tax_payer_id' => '100000001',
            'basic_salary' => 1000,
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'rate_per_hour' => 10,
            'user_id' => $staff->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }

    private function makeDocumentTypeId(User $company): int
    {
        return \Workdo\Hrm\Models\EmployeeDocumentType::query()->create([
            'document_name' => 'Identity',
            'description' => 'Identity document',
            'is_required' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ])->id;
    }

    private function employeeUpdatePayload(array $overrides = []): array
    {
        return array_merge([
            'date_of_birth' => '1992-05-10',
            'gender' => 'Male',
            'shift_id' => null,
            'date_of_joining' => '2024-01-10',
            'employment_type' => 'full_time',
            'address_line_1' => 'Address Updated',
            'address_line_2' => null,
            'city' => 'Maputo',
            'state' => 'Maputo',
            'country' => 'Mozambique',
            'postal_code' => '1100',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_relationship' => 'Sibling',
            'emergency_contact_number' => '840000000',
            'bank_name' => 'Bank',
            'account_holder_name' => 'Holder',
            'account_number' => '123456789',
            'bank_identifier_code' => 'BIMOMZMX',
            'bank_branch' => 'Main',
            'tax_payer_id' => '100000001',
            'basic_salary' => 1500,
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'rate_per_hour' => 12,
            'branch_id' => null,
            'department_id' => null,
            'designation_id' => null,
            'documents' => [],
        ], $overrides);
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
