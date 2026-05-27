<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Models\WarningType;

class HrmDisciplinaryHarassmentOffboardingCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_warning_requires_two_witnesses_when_refusal_is_flagged(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-warnings']);

        $employee = $this->makeStaffUser($company, 'Worker A');
        $warningBy = $this->makeStaffUser($company, 'Supervisor A');

        $warningType = WarningType::query()->create([
            'warning_type_name' => 'Disciplinary',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invalidResponse = $this->actingAs($company)->post(route('hrm.warnings.store'), [
            'employee_id' => $employee->id,
            'warning_by' => $warningBy->id,
            'warning_type_id' => $warningType->id,
            'subject' => 'Incumprimento de horário',
            'severity' => 'Major',
            'warning_date' => '2026-05-27',
            'worker_refused_note_of_culpa' => true,
            'refusal_witness_one_name' => 'Testemunha 1',
        ]);

        $invalidResponse->assertSessionHasErrors('refusal_witness_two_name');

        $validResponse = $this->actingAs($company)->post(route('hrm.warnings.store'), [
            'employee_id' => $employee->id,
            'warning_by' => $warningBy->id,
            'warning_type_id' => $warningType->id,
            'subject' => 'Incumprimento de horário',
            'severity' => 'Major',
            'warning_date' => '2026-05-27',
            'worker_refused_note_of_culpa' => true,
            'refusal_witness_one_name' => 'Testemunha 1',
            'refusal_witness_two_name' => 'Testemunha 2',
            'response_deadline_at' => '2026-05-30',
            'decision_deadline_at' => '2026-06-05',
            'disciplinary_sanction' => 'warning',
        ]);

        $validResponse->assertRedirect(route('hrm.warnings.index'));
        $this->assertDatabaseHas('warnings', [
            'employee_id' => $employee->id,
            'worker_refused_note_of_culpa' => true,
            'refusal_witness_one_name' => 'Testemunha 1',
            'refusal_witness_two_name' => 'Testemunha 2',
            'disciplinary_sanction' => 'warning',
            'created_by' => $company->id,
        ]);
    }

    public function test_complaint_stores_harassment_confidential_flow_fields(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-complaints']);

        $employee = $this->makeStaffUser($company, 'Worker B');
        $againstEmployee = $this->makeStaffUser($company, 'Worker C');
        $handler = $this->makeStaffUser($company, 'HR Handler');

        $complaintType = ComplaintType::query()->create([
            'complaint_type' => 'Assédio',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.complaints.store'), [
            'employee_id' => $employee->id,
            'against_employee_id' => $againstEmployee->id,
            'complaint_type_id' => $complaintType->id,
            'subject' => 'Queixa confidencial',
            'description' => 'Descrição do caso',
            'complaint_date' => '2026-05-27',
            'is_confidential' => true,
            'is_harassment_report' => true,
            'confidential_channel' => 'hotline',
            'confidentiality_level' => 'restricted',
            'handling_owner_id' => $handler->id,
            'investigation_started_at' => '2026-05-27',
        ]);

        $response->assertRedirect(route('hrm.complaints.index'));
        $this->assertDatabaseHas('complaints', [
            'employee_id' => $employee->id,
            'is_confidential' => true,
            'is_harassment_report' => true,
            'confidential_channel' => 'hotline',
            'confidentiality_level' => 'restricted',
            'handling_owner_id' => $handler->id,
            'status' => 'pending',
            'created_by' => $company->id,
        ]);
    }

    public function test_termination_stores_offboarding_checklist_fields(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-terminations']);

        $employee = $this->makeStaffUser($company, 'Worker D');
        $terminationType = TerminationType::query()->create([
            'termination_type' => 'Caducidade',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.terminations.store'), [
            'employee_id' => $employee->id,
            'termination_type_id' => $terminationType->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Fim de contrato',
            'offboarding_letter_delivered_at' => '2026-05-31',
            'offboarding_assets_returned_at' => '2026-05-31',
            'offboarding_access_revoked_at' => '2026-05-31',
            'offboarding_final_payment_at' => '2026-06-01',
            'offboarding_certificate_issued_at' => '2026-06-02',
            'offboarding_inss_notified_at' => '2026-06-03',
            'offboarding_migration_notified_at' => '2026-06-03',
            'offboarding_archive_completed_at' => '2026-06-04',
            'offboarding_completed_at' => '2026-06-04',
            'offboarding_notes' => 'Checklist completo',
        ]);

        $response->assertRedirect(route('hrm.terminations.index'));
        $this->assertDatabaseHas('terminations', [
            'employee_id' => $employee->id,
            'status' => 'pending',
            'offboarding_completed_at' => '2026-06-04 00:00:00',
            'offboarding_notes' => 'Checklist completo',
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
