<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use App\Services\MozambiqueHrComplianceDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\ComplaintType;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\Acknowledgment;
use Workdo\Hrm\Models\DocumentCategory;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;
use Workdo\Hrm\Models\HrmDocument;
use Workdo\Hrm\Models\Resignation;
use Workdo\Hrm\Models\TerminationType;
use Workdo\Hrm\Models\Termination;
use Workdo\Hrm\Models\Warning;
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
        $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-TERM-STORE-001');
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
            'employee_id' => $employeeRecord->user_id,
            'status' => 'pending',
            'offboarding_completed_at' => '2026-06-04 00:00:00',
            'offboarding_notes' => 'Checklist completo',
            'created_by' => $company->id,
        ]);
    }

    public function test_termination_computes_notice_and_settlement_fields(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-terminations']);

        $employee = $this->makeStaffUser($company, 'Worker Settlement A');
        $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-TERM-SETTLE-001');
        $employeeRecord->update([
            'date_of_joining' => '2022-05-01',
            'basic_salary' => 30000,
        ]);

        $terminationType = TerminationType::query()->create([
            'termination_type' => 'Extinção de posto',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->post(route('hrm.terminations.store'), [
            'employee_id' => $employee->id,
            'termination_type_id' => $terminationType->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Reestruturação',
            'settlement_unused_leave_days' => 5,
            'settlement_other_earnings_amount' => 1200,
            'settlement_other_deductions_amount' => 500,
            'settlement_apply_indemnity' => true,
            'settlement_indemnity_days_per_year' => 45,
        ]);

        $response->assertRedirect(route('hrm.terminations.index'));

        $termination = Termination::query()->latest('id')->firstOrFail();
        $this->assertSame(30, $termination->legal_notice_required_days);
        $this->assertSame(30, $termination->legal_notice_provided_days);
        $this->assertSame(0, $termination->legal_notice_missing_days);
        $this->assertTrue((bool) $termination->legal_notice_compliant);

        $this->assertEqualsWithDelta(1000.00, (float) $termination->settlement_daily_salary_amount, 0.01);
        $this->assertEqualsWithDelta(31000.00, (float) $termination->settlement_salary_until_exit_amount, 0.01);
        $this->assertEqualsWithDelta(5000.00, (float) $termination->settlement_unused_leave_amount, 0.01);
        $this->assertGreaterThan(0, (float) $termination->settlement_indemnity_amount);
        $this->assertEqualsWithDelta(
            (float) $termination->settlement_gross_amount - (float) $termination->settlement_total_deductions_amount,
            (float) $termination->settlement_net_amount,
            0.01
        );
    }

    public function test_resignation_computes_notice_gap_and_final_settlement_fields(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');

        try {
            $company = $this->makeCompany();
            $this->grantPermissions($company, ['create-resignations']);

            $employee = $this->makeStaffUser($company, 'Worker Resign Settlement');
            $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-RES-SETTLE-001');
            $employeeRecord->update([
                'date_of_joining' => '2025-01-01',
                'basic_salary' => 15000,
            ]);

            $response = $this->actingAs($company)->post(route('hrm.resignations.store'), [
                'employee_id' => $employee->id,
                'last_working_date' => '2026-06-11',
                'reason' => 'Mudança de carreira',
                'description' => 'Pedido formal de demissão.',
                'settlement_unused_leave_days' => 2,
                'settlement_other_earnings_amount' => 300,
                'settlement_other_deductions_amount' => 100,
                'settlement_apply_indemnity' => false,
            ]);

            $response->assertRedirect(route('hrm.resignations.index'));

            $resignation = Resignation::query()->latest('id')->firstOrFail();
            $this->assertSame(15, $resignation->legal_notice_required_days);
            $this->assertSame(10, $resignation->legal_notice_provided_days);
            $this->assertSame(5, $resignation->legal_notice_missing_days);
            $this->assertFalse((bool) $resignation->legal_notice_compliant);

            $this->assertEqualsWithDelta(500.00, (float) $resignation->settlement_daily_salary_amount, 0.01);
            $this->assertEqualsWithDelta(5500.00, (float) $resignation->settlement_salary_until_exit_amount, 0.01);
            $this->assertEqualsWithDelta(1000.00, (float) $resignation->settlement_unused_leave_amount, 0.01);
            $this->assertEqualsWithDelta(6800.00, (float) $resignation->settlement_gross_amount, 0.01);
            $this->assertEqualsWithDelta(6700.00, (float) $resignation->settlement_net_amount, 0.01);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_resignation_delete_route_soft_cancels_record_with_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-resignations', 'delete-resignations']);

        $employee = $this->makeStaffUser($company, 'Worker Cancel Resignation');
        $this->attachEmployeeProfile($company, $employee, 'EMP-RES-CANCEL-001');

        $this->actingAs($company)->post(route('hrm.resignations.store'), [
            'employee_id' => $employee->id,
            'last_working_date' => '2026-07-15',
            'reason' => 'Pedido pessoal',
        ]);

        $resignation = Resignation::query()->latest('id')->firstOrFail();

        $missingReason = $this->actingAs($company)->delete(route('hrm.resignations.destroy', $resignation->id));
        $missingReason->assertRedirect();
        $missingReason->assertSessionHasErrors('cancellation_reason');

        $response = $this->actingAs($company)->delete(route('hrm.resignations.destroy', $resignation->id), [
            'cancellation_reason' => 'Pedido foi revertido após negociação interna.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('resignations', [
            'id' => $resignation->id,
            'is_cancelled' => 1,
            'cancellation_reason' => 'Pedido foi revertido após negociação interna.',
        ]);
    }

    public function test_accepted_resignation_syncs_foreign_worker_cessation_dates(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-resignations', 'manage-resignation-status']);

        $employee = $this->makeStaffUser($company, 'Worker Foreign Resignation');
        $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-RES-FW-001');

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employeeRecord->id,
            'is_foreign_worker' => true,
            'nationality' => 'AO',
            'residency_status' => 'non_resident',
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.resignations.store'), [
            'employee_id' => $employee->id,
            'last_working_date' => '2026-06-20',
            'reason' => 'Fim de ciclo profissional',
        ]);

        $resignation = Resignation::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($company)->put(route('hrm.resignations.update-status', [$resignation->id, 'accepted']));
        $response->assertRedirect();

        $profile = EmployeeForeignWorkerProfile::query()->where('employee_id', $employeeRecord->id)->firstOrFail();
        $this->assertSame('2026-06-20', optional($profile->cessation_effective_date)->toDateString());
        $this->assertSame('2026-06-25', optional($profile->cessation_notification_due_at)->toDateString());
        $this->assertNull($profile->cessation_notified_at);
    }

    public function test_warning_delete_route_requires_reason_and_soft_cancels_record(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-warnings', 'delete-warnings']);

        $employee = $this->makeStaffUser($company, 'Worker Cancel A');
        $warningBy = $this->makeStaffUser($company, 'Supervisor Cancel A');
        $warningType = WarningType::query()->create([
            'warning_type_name' => 'Disciplinary',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.warnings.store'), [
            'employee_id' => $employee->id,
            'warning_by' => $warningBy->id,
            'warning_type_id' => $warningType->id,
            'subject' => 'Falta injustificada',
            'severity' => 'Minor',
            'warning_date' => '2026-05-27',
        ]);

        $warning = Warning::query()->latest('id')->firstOrFail();

        $missingReason = $this->actingAs($company)->delete(route('hrm.warnings.destroy', $warning->id));
        $missingReason->assertRedirect();
        $missingReason->assertSessionHasErrors('cancellation_reason');

        $response = $this->actingAs($company)->delete(route('hrm.warnings.destroy', $warning->id), [
            'cancellation_reason' => 'Registo duplicado da mesma ocorrência disciplinar.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('warnings', [
            'id' => $warning->id,
            'is_cancelled' => 1,
            'cancellation_reason' => 'Registo duplicado da mesma ocorrência disciplinar.',
        ]);
    }

    public function test_complaint_delete_route_soft_cancels_record_with_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-complaints', 'delete-complaints']);

        $employee = $this->makeStaffUser($company, 'Worker Cancel B');
        $againstEmployee = $this->makeStaffUser($company, 'Worker Cancel C');
        $complaintType = ComplaintType::query()->create([
            'complaint_type' => 'Assédio',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.complaints.store'), [
            'employee_id' => $employee->id,
            'against_employee_id' => $againstEmployee->id,
            'complaint_type_id' => $complaintType->id,
            'subject' => 'Canal interno',
            'description' => 'Descrição',
            'complaint_date' => '2026-05-27',
            'is_harassment_report' => true,
            'is_confidential' => false,
            'confidential_channel' => 'internal',
            'handling_owner_id' => $employee->id,
        ]);

        $complaint = Complaint::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($company)->delete(route('hrm.complaints.destroy', $complaint->id), [
            'cancellation_reason' => 'Queixa duplicada após consolidação do processo.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'is_cancelled' => 1,
            'cancellation_reason' => 'Queixa duplicada após consolidação do processo.',
            'is_confidential' => 1,
        ]);
    }

    public function test_termination_delete_route_soft_cancels_record_with_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-terminations', 'delete-terminations']);

        $employee = $this->makeStaffUser($company, 'Worker Cancel D');
        $this->attachEmployeeProfile($company, $employee, 'EMP-TERM-CANCEL-001');
        $terminationType = TerminationType::query()->create([
            'termination_type' => 'Caducidade',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.terminations.store'), [
            'employee_id' => $employee->id,
            'termination_type_id' => $terminationType->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Fim de contrato',
        ]);

        $termination = Termination::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($company)->delete(route('hrm.terminations.destroy', $termination->id), [
            'cancellation_reason' => 'Rescisão revertida após acordo entre as partes.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('terminations', [
            'id' => $termination->id,
            'is_cancelled' => 1,
            'cancellation_reason' => 'Rescisão revertida após acordo entre as partes.',
        ]);
    }

    public function test_approved_termination_syncs_foreign_worker_cessation_dates(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-terminations', 'manage-termination-status']);

        $employee = $this->makeStaffUser($company, 'Worker Foreign A');
        $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-TERM-FW-001');
        $terminationType = TerminationType::query()->create([
            'termination_type' => 'Caducidade',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employeeRecord->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.terminations.store'), [
            'employee_id' => $employee->id,
            'termination_type_id' => $terminationType->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Fim de contrato',
        ]);

        $termination = Termination::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($company)->put(route('hrm.terminations.update-status', $termination->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();

        $profile = EmployeeForeignWorkerProfile::query()->where('employee_id', $employeeRecord->id)->firstOrFail();
        $this->assertSame('2026-05-31', optional($profile->cessation_effective_date)->toDateString());
        $this->assertSame('2026-06-05', optional($profile->cessation_notification_due_at)->toDateString());
        $this->assertNull($profile->cessation_notified_at);
    }

    public function test_approved_termination_propagates_migration_notification_to_foreign_profile(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-terminations', 'manage-termination-status']);

        $employee = $this->makeStaffUser($company, 'Worker Foreign B');
        $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-TERM-FW-002');
        $terminationType = TerminationType::query()->create([
            'termination_type' => 'Caducidade',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employeeRecord->id,
            'is_foreign_worker' => true,
            'nationality' => 'BR',
            'residency_status' => 'non_resident',
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.terminations.store'), [
            'employee_id' => $employee->id,
            'termination_type_id' => $terminationType->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Fim de contrato',
            'offboarding_migration_notified_at' => '2026-06-02',
        ]);

        $termination = Termination::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($company)->put(route('hrm.terminations.update-status', $termination->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();

        $profile = EmployeeForeignWorkerProfile::query()->where('employee_id', $employeeRecord->id)->firstOrFail();
        $this->assertSame('2026-06-02', optional($profile->cessation_notified_at)->toDateString());
    }

    public function test_approved_probation_termination_closes_probation_profile_and_clears_overdue_alert(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-terminations', 'manage-termination-status']);

        $employee = $this->makeStaffUser($company, 'Probation Worker');
        $employeeRecord = $this->attachEmployeeProfile($company, $employee, 'EMP-PROB-TERM-001');

        EmployeeProbationProfile::query()->create([
            'employee_id' => $employeeRecord->id,
            'probation_category' => 'general',
            'starts_at' => '2026-05-01',
            'expected_end_at' => '2026-07-30',
            'legal_max_days' => 90,
            'evaluation_status' => 'ongoing',
            'decision_status' => 'ongoing',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $terminationType = TerminationType::query()->create([
            'termination_type' => 'Probation Termination',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->post(route('hrm.terminations.store'), [
            'employee_id' => $employee->id,
            'termination_type_id' => $terminationType->id,
            'notice_date' => '2026-06-01',
            'termination_date' => '2026-06-15',
            'reason' => 'Cessação durante período probatório',
            'offboarding_notes' => 'Documentação entregue ao trabalhador.',
        ]);

        $termination = Termination::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($company)->put(route('hrm.terminations.update-status', $termination->id), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();

        $profile = EmployeeProbationProfile::query()->where('employee_id', $employeeRecord->id)->firstOrFail();
        $this->assertSame('ceased', $profile->decision_status);
        $this->assertSame('2026-06-15', optional($profile->decision_date)->toDateString());
        $this->assertSame('Cessação durante período probatório', $profile->cessation_reason);
        $this->assertSame('failed', $profile->evaluation_status);
        $this->assertSame('cease', $profile->recommendation);

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);
        $probationOverdue = collect($snapshot['items'])->firstWhere('key', 'probation_overdue');
        $this->assertNotNull($probationOverdue);
        $this->assertSame(0, (int) ($probationOverdue['count'] ?? 0));
    }

    public function test_termination_store_rejects_cross_company_employee_and_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-terminations']);

        $employeeB = $this->makeStaffUser($companyB, 'Worker External B');
        $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-TERM-EXT-001');
        $terminationTypeB = TerminationType::query()->create([
            'termination_type' => 'Caducidade',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.terminations.store'), [
            'employee_id' => $employeeB->id,
            'termination_type_id' => $terminationTypeB->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Tentativa inválida',
        ]);

        $response->assertSessionHasErrors(['employee_id', 'termination_type_id']);
        $this->assertDatabaseMissing('terminations', [
            'employee_id' => $employeeB->id,
            'termination_type_id' => $terminationTypeB->id,
            'reason' => 'Tentativa inválida',
        ]);
    }

    public function test_termination_update_rejects_cross_company_employee_and_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-terminations', 'edit-terminations', 'manage-any-terminations']);

        $employeeA = $this->makeStaffUser($companyA, 'Worker Internal A');
        $this->attachEmployeeProfile($companyA, $employeeA, 'EMP-TERM-UPD-001');
        $terminationTypeA = TerminationType::query()->create([
            'termination_type' => 'Caducidade',
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $this->actingAs($companyA)->post(route('hrm.terminations.store'), [
            'employee_id' => $employeeA->id,
            'termination_type_id' => $terminationTypeA->id,
            'notice_date' => '2026-05-01',
            'termination_date' => '2026-05-31',
            'reason' => 'Base válida',
        ]);

        $termination = Termination::query()->latest('id')->firstOrFail();

        $employeeB = $this->makeStaffUser($companyB, 'Worker External C');
        $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-TERM-UPD-EXT-001');
        $terminationTypeB = TerminationType::query()->create([
            'termination_type' => 'Projeto',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.terminations.update', $termination->id), [
            'employee_id' => $employeeB->id,
            'termination_type_id' => $terminationTypeB->id,
            'notice_date' => '2026-05-02',
            'termination_date' => '2026-06-01',
            'reason' => 'Tentativa inválida de update',
        ]);

        $response->assertSessionHasErrors(['employee_id', 'termination_type_id']);
        $this->assertDatabaseHas('terminations', [
            'id' => $termination->id,
            'employee_id' => $employeeA->id,
            'termination_type_id' => $terminationTypeA->id,
            'reason' => 'Base válida',
        ]);
    }

    public function test_warning_store_rejects_cross_company_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-warnings']);

        $employeeB = $this->makeStaffUser($companyB, 'Warning External Employee');
        $warningByB = $this->makeStaffUser($companyB, 'Warning External Supervisor');
        $warningTypeB = WarningType::query()->create([
            'warning_type_name' => 'Disciplinary B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.warnings.store'), [
            'employee_id' => $employeeB->id,
            'warning_by' => $warningByB->id,
            'warning_type_id' => $warningTypeB->id,
            'subject' => 'Tentativa cross-company warning',
            'severity' => 'Minor',
            'warning_date' => '2026-05-27',
        ]);

        $response->assertSessionHasErrors(['employee_id', 'warning_by', 'warning_type_id']);
        $this->assertDatabaseMissing('warnings', [
            'subject' => 'Tentativa cross-company warning',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_warning_update_rejects_cross_company_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-warnings', 'edit-warnings']);

        $employeeA = $this->makeStaffUser($companyA, 'Warning Internal Employee');
        $warningByA = $this->makeStaffUser($companyA, 'Warning Internal Supervisor');
        $warningTypeA = WarningType::query()->create([
            'warning_type_name' => 'Disciplinary A',
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $this->actingAs($companyA)->post(route('hrm.warnings.store'), [
            'employee_id' => $employeeA->id,
            'warning_by' => $warningByA->id,
            'warning_type_id' => $warningTypeA->id,
            'subject' => 'Warning base válida',
            'severity' => 'Minor',
            'warning_date' => '2026-05-27',
        ]);

        $warning = Warning::query()->latest('id')->firstOrFail();

        $employeeB = $this->makeStaffUser($companyB, 'Warning External Employee');
        $warningByB = $this->makeStaffUser($companyB, 'Warning External Supervisor');
        $warningTypeB = WarningType::query()->create([
            'warning_type_name' => 'Disciplinary B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.warnings.update', $warning->id), [
            'employee_id' => $employeeB->id,
            'warning_by' => $warningByB->id,
            'warning_type_id' => $warningTypeB->id,
            'subject' => 'Tentativa update inválida',
            'severity' => 'Major',
            'warning_date' => '2026-05-28',
        ]);

        $response->assertSessionHasErrors(['employee_id', 'warning_by', 'warning_type_id']);
        $this->assertDatabaseHas('warnings', [
            'id' => $warning->id,
            'employee_id' => $employeeA->id,
            'warning_by' => $warningByA->id,
            'warning_type_id' => $warningTypeA->id,
            'subject' => 'Warning base válida',
        ]);
    }

    public function test_complaint_store_rejects_cross_company_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-complaints']);

        $employeeB = $this->makeStaffUser($companyB, 'Complaint External Employee');
        $againstB = $this->makeStaffUser($companyB, 'Complaint External Against');
        $handlerB = $this->makeStaffUser($companyB, 'Complaint External Handler');
        $reviewerB = $this->makeStaffUser($companyB, 'Complaint External Reviewer');
        $complaintTypeB = ComplaintType::query()->create([
            'complaint_type' => 'Assédio B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.complaints.store'), [
            'employee_id' => $employeeB->id,
            'against_employee_id' => $againstB->id,
            'complaint_type_id' => $complaintTypeB->id,
            'subject' => 'Tentativa cross-company complaint',
            'description' => 'Descrição inválida',
            'complaint_date' => '2026-05-27',
            'is_confidential' => true,
            'confidential_channel' => 'hotline',
            'confidential_access_user_ids' => [$reviewerB->id],
            'handling_owner_id' => $handlerB->id,
        ]);

        $response->assertSessionHasErrors([
            'employee_id',
            'against_employee_id',
            'complaint_type_id',
            'handling_owner_id',
            'confidential_access_user_ids.0',
        ]);
        $this->assertDatabaseMissing('complaints', [
            'subject' => 'Tentativa cross-company complaint',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_complaint_update_rejects_cross_company_references(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-complaints', 'edit-complaints']);

        $employeeA = $this->makeStaffUser($companyA, 'Complaint Internal Employee');
        $againstA = $this->makeStaffUser($companyA, 'Complaint Internal Against');
        $handlerA = $this->makeStaffUser($companyA, 'Complaint Internal Handler');
        $complaintTypeA = ComplaintType::query()->create([
            'complaint_type' => 'Assédio A',
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $this->actingAs($companyA)->post(route('hrm.complaints.store'), [
            'employee_id' => $employeeA->id,
            'against_employee_id' => $againstA->id,
            'complaint_type_id' => $complaintTypeA->id,
            'subject' => 'Complaint base válida',
            'description' => 'Descrição base',
            'complaint_date' => '2026-05-27',
            'is_confidential' => true,
            'confidential_channel' => 'internal',
            'handling_owner_id' => $handlerA->id,
        ]);

        $complaint = Complaint::query()->latest('id')->firstOrFail();

        $employeeB = $this->makeStaffUser($companyB, 'Complaint External Employee');
        $againstB = $this->makeStaffUser($companyB, 'Complaint External Against');
        $handlerB = $this->makeStaffUser($companyB, 'Complaint External Handler');
        $reviewerB = $this->makeStaffUser($companyB, 'Complaint External Reviewer');
        $complaintTypeB = ComplaintType::query()->create([
            'complaint_type' => 'Assédio B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.complaints.update', $complaint->id), [
            'employee_id' => $employeeB->id,
            'against_employee_id' => $againstB->id,
            'complaint_type_id' => $complaintTypeB->id,
            'subject' => 'Tentativa update inválida',
            'description' => 'Descrição inválida',
            'complaint_date' => '2026-05-28',
            'is_confidential' => true,
            'confidential_channel' => 'internal',
            'confidential_access_user_ids' => [$reviewerB->id],
            'handling_owner_id' => $handlerB->id,
        ]);

        $response->assertSessionHasErrors([
            'employee_id',
            'against_employee_id',
            'complaint_type_id',
            'handling_owner_id',
            'confidential_access_user_ids.0',
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'employee_id' => $employeeA->id,
            'against_employee_id' => $againstA->id,
            'complaint_type_id' => $complaintTypeA->id,
            'handling_owner_id' => $handlerA->id,
            'subject' => 'Complaint base válida',
        ]);
    }

    public function test_confidential_complaints_are_hidden_without_confidential_permission(): void
    {
        $company = $this->makeCompany();
        $manager = $this->makeStaffUser($company, 'Gestor RH');
        $reporter = $this->makeStaffUser($company, 'Reporter');
        $against = $this->makeStaffUser($company, 'Against');
        $handler = $this->makeStaffUser($company, 'Handler');

        $this->grantPermissions($manager, ['manage-complaints', 'manage-any-complaints']);

        $complaintType = ComplaintType::query()->create([
            'complaint_type' => 'Assédio',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Complaint::query()->create([
            'employee_id' => $reporter->id,
            'against_employee_id' => $against->id,
            'complaint_type_id' => $complaintType->id,
            'subject' => 'Caso Confidencial RH',
            'description' => 'Descricao sigilosa',
            'complaint_date' => '2026-05-27',
            'status' => 'pending',
            'is_confidential' => true,
            'is_harassment_report' => true,
            'confidential_channel' => 'hotline',
            'confidentiality_level' => 'restricted',
            'handling_owner_id' => $handler->id,
            'creator_id' => $reporter->id,
            'created_by' => $company->id,
        ]);

        Complaint::query()->create([
            'employee_id' => $reporter->id,
            'against_employee_id' => $against->id,
            'complaint_type_id' => $complaintType->id,
            'subject' => 'Caso Publico RH',
            'description' => 'Descricao publica',
            'complaint_date' => '2026-05-27',
            'status' => 'pending',
            'is_confidential' => false,
            'is_harassment_report' => false,
            'confidential_channel' => 'internal',
            'confidentiality_level' => 'internal',
            'creator_id' => $reporter->id,
            'created_by' => $company->id,
        ]);

        $withoutPermission = $this->actingAs($manager)->get(route('hrm.complaints.index'));

        $withoutPermission->assertOk();
        $withoutPermission->assertSee('Caso Publico RH', false);
        $withoutPermission->assertDontSee('Caso Confidencial RH', false);

        $this->grantPermissions($manager, ['manage-confidential-complaints']);

        $withPermission = $this->actingAs($manager)->get(route('hrm.complaints.index'));

        $withPermission->assertOk();
        $withPermission->assertSee('Caso Confidencial RH', false);
    }

    public function test_confidential_complaint_supports_explicit_access_list_without_global_confidential_permission(): void
    {
        $company = $this->makeCompany();
        $reviewer = $this->makeStaffUser($company, 'Reviewer');
        $outsider = $this->makeStaffUser($company, 'Outsider');
        $reporter = $this->makeStaffUser($company, 'Reporter');
        $against = $this->makeStaffUser($company, 'Against');
        $handler = $this->makeStaffUser($company, 'Handler');

        $this->grantPermissions($reviewer, ['manage-complaints', 'manage-own-complaints']);
        $this->grantPermissions($outsider, ['manage-complaints', 'manage-own-complaints']);

        $complaintType = ComplaintType::query()->create([
            'complaint_type' => 'Assédio',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Complaint::query()->create([
            'employee_id' => $reporter->id,
            'against_employee_id' => $against->id,
            'complaint_type_id' => $complaintType->id,
            'subject' => 'Caso Restrito RH',
            'description' => 'Descricao restrita',
            'complaint_date' => '2026-05-27',
            'status' => 'assigned',
            'is_confidential' => true,
            'is_harassment_report' => true,
            'confidential_channel' => 'hotline',
            'confidentiality_level' => 'restricted',
            'confidential_access_user_ids' => [$reviewer->id],
            'handling_owner_id' => $handler->id,
            'creator_id' => $reporter->id,
            'created_by' => $company->id,
        ]);

        $reviewerResponse = $this->actingAs($reviewer)->get(route('hrm.complaints.index'));
        $reviewerResponse->assertOk();
        $reviewerResponse->assertSee('Caso Restrito RH', false);

        $outsiderResponse = $this->actingAs($outsider)->get(route('hrm.complaints.index'));
        $outsiderResponse->assertOk();
        $outsiderResponse->assertDontSee('Caso Restrito RH', false);
    }

    public function test_cross_company_warning_cannot_be_cancelled(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-warnings']);

        $employeeB = $this->makeStaffUser($companyB, 'Worker B');
        $warningByB = $this->makeStaffUser($companyB, 'Supervisor B');

        $warning = Warning::query()->create([
            'employee_id' => $employeeB->id,
            'warning_by' => $warningByB->id,
            'warning_type_id' => null,
            'subject' => 'Registo Empresa B',
            'severity' => 'Minor',
            'warning_date' => '2026-05-27',
            'status' => 'pending',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.warnings.destroy', $warning->id), [
            'cancellation_reason' => 'Tentativa indevida',
        ]);

        $response->assertRedirect(route('hrm.warnings.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('warnings', [
            'id' => $warning->id,
            'is_cancelled' => 0,
        ]);
    }

    public function test_acknowledgment_store_blocks_cross_company_document_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-acknowledgments']);

        $employeeA = $this->makeStaffUser($companyA, 'Ack Worker A');

        Employee::query()->create([
            'employee_id' => 'EMP-ACK-A',
            'user_id' => $employeeA->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400123456',
            'basic_salary' => 10000,
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $foreignDocument = HrmDocument::query()->create([
            'title' => 'Código de Conduta - Empresa B',
            'description' => 'Documento externo',
            'status' => 'approve',
            'uploaded_by' => $companyB->id,
            'approved_by' => $companyB->id,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.acknowledgments.store'), [
            'employee_id' => $employeeA->id,
            'document_id' => $foreignDocument->id,
            'acknowledgment_note' => 'Tentativa indevida',
        ]);

        $response->assertSessionHasErrors('document_id');
        $this->assertDatabaseCount('acknowledgments', 0);
    }

    public function test_acknowledgment_store_blocks_duplicate_employee_document_signoff(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-acknowledgments']);

        $employee = $this->makeStaffUser($company, 'Ack Worker Duplicate');

        Employee::query()->create([
            'employee_id' => 'EMP-ACK-DUP',
            'user_id' => $employee->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400777123',
            'basic_salary' => 12000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $document = HrmDocument::query()->create([
            'title' => 'Código de Conduta',
            'description' => 'Política interna obrigatória',
            'status' => 'approve',
            'uploaded_by' => $company->id,
            'approved_by' => $company->id,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $firstResponse = $this->actingAs($company)->post(route('hrm.acknowledgments.store'), [
            'employee_id' => $employee->id,
            'document_id' => $document->id,
            'acknowledgment_note' => 'Primeira ciência',
        ]);

        $firstResponse->assertRedirect(route('hrm.acknowledgments.index'));
        $firstResponse->assertSessionHas('success');
        $this->assertDatabaseCount('acknowledgments', 1);

        $secondResponse = $this->actingAs($company)->post(route('hrm.acknowledgments.store'), [
            'employee_id' => $employee->id,
            'document_id' => $document->id,
            'acknowledgment_note' => 'Tentativa duplicada',
        ]);

        $secondResponse->assertRedirect(route('hrm.acknowledgments.index'));
        $secondResponse->assertSessionHas('error', 'This employee has already acknowledged this document.');
        $this->assertDatabaseCount('acknowledgments', 1);
    }

    public function test_cross_company_acknowledgment_cannot_be_deleted(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-acknowledgments']);

        $employeeB = $this->makeStaffUser($companyB, 'Ack Worker B');

        Employee::query()->create([
            'employee_id' => 'EMP-ACK-B',
            'user_id' => $employeeB->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400654321',
            'basic_salary' => 11000,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $documentB = HrmDocument::query()->create([
            'title' => 'Código de Conduta - Empresa B',
            'description' => 'Documento da empresa B',
            'status' => 'approve',
            'uploaded_by' => $companyB->id,
            'approved_by' => $companyB->id,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $acknowledgment = Acknowledgment::query()->create([
            'employee_id' => $employeeB->id,
            'document_id' => $documentB->id,
            'status' => 'pending',
            'assigned_by' => $companyB->id,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.acknowledgments.destroy', $acknowledgment->id));

        $response->assertRedirect(route('hrm.acknowledgments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('acknowledgments', [
            'id' => $acknowledgment->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_cross_company_document_category_cannot_be_deleted(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-document-categories']);

        $documentCategory = DocumentCategory::query()->create([
            'document_type' => 'Legal Documents',
            'status' => true,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.document-categories.destroy', $documentCategory->id));

        $response->assertRedirect(route('hrm.document-categories.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('document_categories', [
            'id' => $documentCategory->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_cross_company_document_status_cannot_be_updated(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['manage-hrm-documents-status']);

        $categoryB = DocumentCategory::query()->create([
            'document_type' => 'Legal Documents',
            'status' => true,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $document = HrmDocument::query()->create([
            'title' => 'Política Interna B',
            'description' => 'Documento B',
            'document_category_id' => $categoryB->id,
            'status' => 'pending',
            'uploaded_by' => $companyB->id,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.documents.update-status', $document->id), [
            'status' => 'approve',
        ]);

        $response->assertRedirect(route('hrm.documents.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('hrm_documents', [
            'id' => $document->id,
            'status' => 'pending',
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

    private function attachEmployeeProfile(User $company, User $staff, string $employeeCode): Employee
    {
        return Employee::query()->create([
            'employee_id' => $employeeCode,
            'user_id' => $staff->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400000001',
            'basic_salary' => 10000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
    }
}
