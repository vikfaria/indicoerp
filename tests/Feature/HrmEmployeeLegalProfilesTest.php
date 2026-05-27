<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDependent;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;

class HrmEmployeeLegalProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_can_upsert_employee_inss_profile(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);
        $employee = $this->makeEmployee($company, 'EMP-INSS-001');

        $response = $this->actingAs($company)->put(
            route('hrm.employees.social-security-profile.upsert', $employee->id),
            [
                'inss_number' => 'INSS-900123',
                'registration_date' => '2026-05-20',
                'registration_status' => 'registered',
                'identification_document_type' => 'BI',
                'identification_document_number' => '1101020304050A',
                'evidence_file_path' => '/docs/inss/employee.pdf',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('employee_social_security_profiles', [
            'employee_id' => $employee->id,
            'inss_number' => 'INSS-900123',
            'registration_status' => 'registered',
            'created_by' => $company->id,
        ]);
    }

    public function test_blocks_foreign_worker_profile_when_quota_is_exceeded(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);

        $employees = collect();
        for ($i = 1; $i <= 10; $i++) {
            $employees->push($this->makeEmployee($company, 'EMP-FW-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)));
        }

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employees[0]->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(
            route('hrm.employees.foreign-worker-profile.upsert', $employees[1]->id),
            [
                'is_foreign_worker' => true,
                'nationality' => 'ZA',
                'residency_status' => 'non_resident',
                'hiring_regime' => 'quota',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('employee_foreign_worker_profiles', [
            'employee_id' => $employees[1]->id,
            'is_foreign_worker' => true,
        ]);
    }

    public function test_can_create_update_and_delete_employee_dependent(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);
        $employee = $this->makeEmployee($company, 'EMP-DEP-001');

        $createResponse = $this->actingAs($company)->post(
            route('hrm.employees.dependents.store', $employee->id),
            [
                'full_name' => 'Maria Ana',
                'relationship' => 'child',
                'date_of_birth' => '2016-03-15',
                'document_number' => 'DOC-12345',
                'is_student' => true,
                'is_tax_eligible' => true,
                'valid_until' => '2030-12-31',
                'notes' => 'Dependente escolar',
            ]
        );

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('employee_dependents', [
            'employee_id' => $employee->id,
            'full_name' => 'Maria Ana',
            'relationship' => 'child',
            'created_by' => $company->id,
        ]);

        $dependent = EmployeeDependent::query()->where('employee_id', $employee->id)->firstOrFail();

        $updateResponse = $this->actingAs($company)->put(
            route('hrm.employees.dependents.update', [$employee->id, $dependent->id]),
            [
                'full_name' => 'Maria Ana Santos',
                'relationship' => 'child',
                'date_of_birth' => '2016-03-15',
                'document_number' => 'DOC-12345',
                'is_student' => true,
                'is_tax_eligible' => false,
                'valid_until' => '2030-12-31',
                'notes' => 'Atualizado',
            ]
        );

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('employee_dependents', [
            'id' => $dependent->id,
            'full_name' => 'Maria Ana Santos',
            'is_tax_eligible' => false,
        ]);

        $deleteResponse = $this->actingAs($company)->delete(
            route('hrm.employees.dependents.destroy', [$employee->id, $dependent->id])
        );

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('employee_dependents', [
            'id' => $dependent->id,
        ]);
    }

    public function test_foreign_worker_cessation_notification_must_be_within_five_days(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);

        $employee = $this->makeEmployee($company, 'EMP-FW-CESS-001');

        $lateResponse = $this->actingAs($company)->put(
            route('hrm.employees.foreign-worker-profile.upsert', $employee->id),
            [
                'is_foreign_worker' => true,
                'nationality' => 'BR',
                'residency_status' => 'non_resident',
                'hiring_regime' => 'quota',
                'cessation_effective_date' => '2026-06-01',
                'cessation_notified_at' => '2026-06-10',
            ]
        );

        $lateResponse->assertSessionHasErrors('cessation_notified_at');

        $onTimeResponse = $this->actingAs($company)->put(
            route('hrm.employees.foreign-worker-profile.upsert', $employee->id),
            [
                'is_foreign_worker' => true,
                'nationality' => 'BR',
                'residency_status' => 'non_resident',
                'hiring_regime' => 'quota',
                'cessation_effective_date' => '2026-06-01',
                'cessation_notified_at' => '2026-06-05',
            ]
        );

        $onTimeResponse->assertRedirect();
        $this->assertDatabaseHas('employee_foreign_worker_profiles', [
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
        ]);

        $profile = EmployeeForeignWorkerProfile::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-06-01', optional($profile->cessation_effective_date)->toDateString());
        $this->assertSame('2026-06-06', optional($profile->cessation_notification_due_at)->toDateString());
        $this->assertSame('2026-06-05', optional($profile->cessation_notified_at)->toDateString());
    }

    public function test_can_upsert_probation_profile_with_legal_limit_enforcement(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-employees', 'manage-any-employees']);
        $employee = $this->makeEmployee($company, 'EMP-PROB-001');

        $validResponse = $this->actingAs($company)->put(
            route('hrm.employees.probation-profile.upsert', $employee->id),
            [
                'probation_category' => 'technician_mid',
                'starts_at' => '2026-05-01',
                'expected_end_at' => '2026-07-30',
                'evaluation_status' => 'pending',
                'decision_status' => 'ongoing',
            ]
        );

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('employee_probation_profiles', [
            'employee_id' => $employee->id,
            'probation_category' => 'technician_mid',
            'legal_max_days' => 90,
        ]);

        $savedProfile = EmployeeProbationProfile::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-07-30', optional($savedProfile->expected_end_at)->toDateString());

        $invalidResponse = $this->actingAs($company)->put(
            route('hrm.employees.probation-profile.upsert', $employee->id),
            [
                'probation_category' => 'technician_mid',
                'starts_at' => '2026-05-01',
                'expected_end_at' => '2026-08-15',
                'evaluation_status' => 'pending',
                'decision_status' => 'ongoing',
            ]
        );

        $invalidResponse->assertSessionHasErrors('expected_end_at');

        $profile = EmployeeProbationProfile::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-07-30', optional($profile->expected_end_at)->toDateString());
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

    private function makeEmployee(User $company, string $employeeCode): Employee
    {
        $staff = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        return Employee::query()->create([
            'employee_id' => $employeeCode,
            'user_id' => $staff->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 10000,
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
