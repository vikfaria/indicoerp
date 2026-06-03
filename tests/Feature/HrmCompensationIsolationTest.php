<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Hrm\Models\Allowance;
use Workdo\Hrm\Models\AllowanceType;
use Workdo\Hrm\Models\Deduction;
use Workdo\Hrm\Models\DeductionType;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Loan;
use Workdo\Hrm\Models\LoanType;
use Workdo\Hrm\Models\Overtime;

class HrmCompensationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_overtime_store_rejects_cross_company_employee_profile(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-overtimes']);

        $employeeB = $this->makeStaffUser($companyB, 'OT External Worker');
        $employeeProfileB = $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-OT-EXT-001');

        $response = $this->actingAs($companyA)->post(route('hrm.overtimes.store'), [
            'employee_id' => $employeeProfileB->id,
            'title' => 'Overtime cross-company attempt',
            'total_days' => 1,
            'hours' => 2,
            'rate' => 100,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'notes' => null,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('overtimes', 0);
    }

    public function test_allowance_store_rejects_cross_company_employee_and_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-allowances']);

        $employeeB = $this->makeStaffUser($companyB, 'Allowance External Worker');
        $employeeProfileB = $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-ALL-EXT-001');
        $allowanceTypeB = AllowanceType::query()->create([
            'name' => 'Transport Allowance B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.allowances.store'), [
            'employee_id' => $employeeProfileB->id,
            'allowance_type_id' => $allowanceTypeB->id,
            'type' => 'fixed',
            'amount' => 500,
        ]);

        $response->assertSessionHasErrors(['employee_id', 'allowance_type_id']);
        $this->assertDatabaseCount('allowances', 0);
    }

    public function test_deduction_store_rejects_cross_company_employee_and_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-deductions']);

        $employeeB = $this->makeStaffUser($companyB, 'Deduction External Worker');
        $employeeProfileB = $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-DED-EXT-001');
        $deductionTypeB = DeductionType::query()->create([
            'name' => 'Tax Deduction B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.deductions.store'), [
            'employee_id' => $employeeProfileB->id,
            'deduction_type_id' => $deductionTypeB->id,
            'type' => 'fixed',
            'amount' => 300,
        ]);

        $response->assertSessionHasErrors(['employee_id', 'deduction_type_id']);
        $this->assertDatabaseCount('deductions', 0);
    }

    public function test_loan_store_rejects_cross_company_employee_and_type(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['create-loans']);

        $employeeB = $this->makeStaffUser($companyB, 'Loan External Worker');
        $employeeProfileB = $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-LOAN-EXT-001');
        $loanTypeB = LoanType::query()->create([
            'name' => 'Personal Loan B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->post(route('hrm.loans.store'), [
            'employee_id' => $employeeProfileB->id,
            'title' => 'Cross-company loan attempt',
            'loan_type_id' => $loanTypeB->id,
            'type' => 'fixed',
            'amount' => 1000,
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-01',
            'reason' => 'Test',
        ]);

        $response->assertSessionHasErrors(['employee_id', 'loan_type_id']);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_overtime_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-overtimes']);

        $employeeB = $this->makeStaffUser($companyB, 'OT External Worker');
        $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-OT-UPD-EXT-001');

        $overtime = Overtime::query()->create([
            'title' => 'External overtime',
            'employee_id' => $employeeB->id,
            'total_days' => 1,
            'hours' => 2,
            'rate' => 120,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'status' => 'active',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.overtimes.update', $overtime->id), [
            'title' => 'Attempted update',
            'total_days' => 2,
            'hours' => 3,
            'rate' => 130,
            'start_date' => '2026-06-02',
            'end_date' => '2026-06-02',
            'notes' => 'Should fail',
            'status' => 'active',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'title' => 'External overtime',
            'created_by' => $companyB->id,
        ]);
    }

    public function test_overtime_store_marks_entry_as_pending_approval(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-overtimes']);

        $employeeUser = $this->makeStaffUser($company, 'OT Internal Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-OT-PENDING-001');

        $response = $this->actingAs($company)->post(route('hrm.overtimes.store'), [
            'employee_id' => $employee->id,
            'title' => 'Pending overtime',
            'total_days' => 1,
            'hours' => 2,
            'rate' => 120,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-01',
            'notes' => 'Created for approval workflow',
            'status' => 'active',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('overtimes', [
            'created_by' => $company->id,
            'employee_id' => $employeeUser->id,
            'title' => 'Pending overtime',
            'status' => 'expired',
            'approval_status' => 'pending',
        ]);
    }

    public function test_overtime_status_update_approves_pending_entry(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-overtimes']);

        $employeeUser = $this->makeStaffUser($company, 'OT Approve Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-OT-APPROVE-001');

        $overtime = Overtime::query()->create([
            'title' => 'Needs approval',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 3,
            'rate' => 100,
            'start_date' => '2026-06-05',
            'end_date' => '2026-06-05',
            'status' => 'expired',
            'approval_status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(route('hrm.overtimes.update-status', $overtime->id), [
            'status' => 'approved',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'status' => 'active',
            'approval_status' => 'approved',
            'approved_by' => $company->id,
        ]);
    }

    public function test_overtime_status_rejection_requires_reason(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['edit-overtimes']);

        $employeeUser = $this->makeStaffUser($company, 'OT Reject Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-OT-REJECT-001');

        $overtime = Overtime::query()->create([
            'title' => 'Reject without reason',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 2,
            'rate' => 90,
            'start_date' => '2026-06-07',
            'end_date' => '2026-06-07',
            'status' => 'expired',
            'approval_status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->put(route('hrm.overtimes.update-status', $overtime->id), [
            'status' => 'rejected',
        ]);

        $response->assertSessionHasErrors('rejection_reason');
        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'approval_status' => 'pending',
        ]);
    }

    public function test_allowance_update_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-allowances']);

        $employeeB = $this->makeStaffUser($companyB, 'Allowance External Worker');
        $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-ALL-UPD-EXT-001');
        $allowanceTypeB = AllowanceType::query()->create([
            'name' => 'Transport Allowance B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $allowance = Allowance::query()->create([
            'employee_id' => $employeeB->id,
            'allowance_type_id' => $allowanceTypeB->id,
            'type' => 'fixed',
            'amount' => 700,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.allowances.update', $allowance->id), [
            'allowance_type_id' => $allowanceTypeB->id,
            'type' => 'percentage',
            'amount' => 10,
        ]);

        $response->assertSessionHasErrors(['allowance_type_id']);
        $this->assertDatabaseHas('allowances', [
            'id' => $allowance->id,
            'type' => 'fixed',
            'amount' => 700,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_loan_update_rejects_cross_company_loan_type_reference(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['edit-loans']);

        $employeeA = $this->makeStaffUser($companyA, 'Loan Internal Worker');
        $this->attachEmployeeProfile($companyA, $employeeA, 'EMP-LOAN-UPD-INT-001');

        $loanTypeA = LoanType::query()->create([
            'name' => 'Personal Loan A',
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);
        $loanTypeB = LoanType::query()->create([
            'name' => 'Personal Loan B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $loan = Loan::query()->create([
            'title' => 'Internal loan',
            'employee_id' => $employeeA->id,
            'loan_type_id' => $loanTypeA->id,
            'type' => 'fixed',
            'amount' => 1000,
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-01',
            'reason' => 'Base',
            'creator_id' => $companyA->id,
            'created_by' => $companyA->id,
        ]);

        $response = $this->actingAs($companyA)->put(route('hrm.loans.update', $loan->id), [
            'title' => 'Attempted invalid type',
            'loan_type_id' => $loanTypeB->id,
            'type' => 'fixed',
            'amount' => 1200,
            'start_date' => '2026-06-02',
            'end_date' => '2026-07-02',
            'reason' => 'Update',
        ]);

        $response->assertSessionHasErrors('loan_type_id');
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'loan_type_id' => $loanTypeA->id,
            'title' => 'Internal loan',
            'created_by' => $companyA->id,
        ]);
    }

    public function test_deduction_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-deductions']);

        $employeeB = $this->makeStaffUser($companyB, 'Deduction External Worker');
        $employeeProfileB = $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-DED-DEL-EXT-001');
        $deductionTypeB = DeductionType::query()->create([
            'name' => 'Tax Deduction B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $deduction = Deduction::query()->create([
            'employee_id' => $employeeB->id,
            'deduction_type_id' => $deductionTypeB->id,
            'type' => 'fixed',
            'amount' => 500,
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.deductions.destroy', [$deduction->id, $employeeProfileB->id]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('deductions', [
            'id' => $deduction->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_loan_destroy_denies_cross_company_record_access(): void
    {
        $companyA = $this->makeCompany();
        $companyB = $this->makeCompany();

        $this->grantPermissions($companyA, ['delete-loans']);

        $employeeB = $this->makeStaffUser($companyB, 'Loan External Worker');
        $employeeProfileB = $this->attachEmployeeProfile($companyB, $employeeB, 'EMP-LOAN-DEL-EXT-001');
        $loanTypeB = LoanType::query()->create([
            'name' => 'Personal Loan B',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $loan = Loan::query()->create([
            'title' => 'External loan',
            'employee_id' => $employeeB->id,
            'loan_type_id' => $loanTypeB->id,
            'type' => 'fixed',
            'amount' => 2200,
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-01',
            'reason' => 'Base',
            'creator_id' => $companyB->id,
            'created_by' => $companyB->id,
        ]);

        $response = $this->actingAs($companyA)->delete(route('hrm.loans.destroy', [$loan->id, $employeeProfileB->id]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'created_by' => $companyB->id,
        ]);
    }

    public function test_allowance_destroy_requires_reason_and_soft_cancels_record(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['delete-allowances']);

        $employeeUser = $this->makeStaffUser($company, 'Allowance Cancel Worker');
        $this->attachEmployeeProfile($company, $employeeUser, 'EMP-ALL-CANCEL-001');

        $allowanceType = AllowanceType::query()->create([
            'name' => 'Allowance Cancel Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $allowance = Allowance::query()->create([
            'employee_id' => $employeeUser->id,
            'allowance_type_id' => $allowanceType->id,
            'type' => 'fixed',
            'amount' => 900,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingReasonResponse = $this->actingAs($company)->delete(route('hrm.allowances.destroy', $allowance->id));
        $missingReasonResponse->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('allowances', [
            'id' => $allowance->id,
            'is_cancelled' => false,
        ]);

        $reason = 'Allowance duplicado substituido por novo registo.';
        $response = $this->actingAs($company)->delete(route('hrm.allowances.destroy', $allowance->id), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('allowances', [
            'id' => $allowance->id,
            'is_cancelled' => true,
            'cancelled_by' => $company->id,
            'cancellation_reason' => $reason,
        ]);
    }

    public function test_deduction_destroy_requires_reason_and_soft_cancels_record(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['delete-deductions']);

        $employeeUser = $this->makeStaffUser($company, 'Deduction Cancel Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-DED-CANCEL-001');

        $deductionType = DeductionType::query()->create([
            'name' => 'Deduction Cancel Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $deduction = Deduction::query()->create([
            'employee_id' => $employeeUser->id,
            'deduction_type_id' => $deductionType->id,
            'type' => 'fixed',
            'amount' => 400,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingReasonResponse = $this->actingAs($company)->delete(route('hrm.deductions.destroy', [$deduction->id, $employee->id]));
        $missingReasonResponse->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('deductions', [
            'id' => $deduction->id,
            'is_cancelled' => false,
        ]);

        $reason = 'Dedução anterior anulada por erro de parametrização.';
        $response = $this->actingAs($company)->delete(route('hrm.deductions.destroy', [$deduction->id, $employee->id]), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('deductions', [
            'id' => $deduction->id,
            'is_cancelled' => true,
            'cancelled_by' => $company->id,
            'cancellation_reason' => $reason,
        ]);
    }

    public function test_loan_destroy_requires_reason_and_soft_cancels_record(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['delete-loans']);

        $employeeUser = $this->makeStaffUser($company, 'Loan Cancel Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LOAN-CANCEL-001');

        $loanType = LoanType::query()->create([
            'name' => 'Loan Cancel Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $loan = Loan::query()->create([
            'title' => 'Loan cancel test',
            'employee_id' => $employeeUser->id,
            'loan_type_id' => $loanType->id,
            'type' => 'fixed',
            'amount' => 1800,
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-01',
            'reason' => 'Testing cancel flow',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingReasonResponse = $this->actingAs($company)->delete(route('hrm.loans.destroy', [$loan->id, $employee->id]));
        $missingReasonResponse->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'is_cancelled' => false,
        ]);

        $reason = 'Empréstimo cancelado após revisão documental.';
        $response = $this->actingAs($company)->delete(route('hrm.loans.destroy', [$loan->id, $employee->id]), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'is_cancelled' => true,
            'cancelled_by' => $company->id,
            'cancellation_reason' => $reason,
        ]);
    }

    public function test_overtime_destroy_requires_reason_and_soft_cancels_record(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['delete-overtimes']);

        $employeeUser = $this->makeStaffUser($company, 'Overtime Cancel Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-OT-CANCEL-001');

        $overtime = Overtime::query()->create([
            'title' => 'Overtime cancel test',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 4,
            'rate' => 120,
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15',
            'status' => 'active',
            'approval_status' => 'approved',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingReasonResponse = $this->actingAs($company)->delete(route('hrm.overtimes.destroy', [$overtime->id, $employee->id]));
        $missingReasonResponse->assertSessionHasErrors('cancellation_reason');

        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'is_cancelled' => false,
        ]);

        $reason = 'Horas extra canceladas após correção de ponto.';
        $response = $this->actingAs($company)->delete(route('hrm.overtimes.destroy', [$overtime->id, $employee->id]), [
            'cancellation_reason' => $reason,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('overtimes', [
            'id' => $overtime->id,
            'is_cancelled' => true,
            'cancelled_by' => $company->id,
            'cancellation_reason' => $reason,
        ]);
    }

    public function test_cancelled_allowance_does_not_block_creation_of_same_type(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-allowances', 'delete-allowances']);

        $employeeUser = $this->makeStaffUser($company, 'Allowance Recreate Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-ALL-RECREATE-001');

        $allowanceType = AllowanceType::query()->create([
            'name' => 'Allowance Recreate Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $allowance = Allowance::query()->create([
            'employee_id' => $employeeUser->id,
            'allowance_type_id' => $allowanceType->id,
            'type' => 'fixed',
            'amount' => 500,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->delete(route('hrm.allowances.destroy', $allowance->id), [
            'cancellation_reason' => 'Registo inicial cancelado para novo valor.',
        ]);

        $response = $this->actingAs($company)->post(route('hrm.allowances.store'), [
            'employee_id' => $employee->id,
            'allowance_type_id' => $allowanceType->id,
            'type' => 'fixed',
            'amount' => 650,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseCount('allowances', 2);
        $this->assertDatabaseHas('allowances', [
            'employee_id' => $employeeUser->id,
            'allowance_type_id' => $allowanceType->id,
            'amount' => 650,
            'is_cancelled' => false,
        ]);
    }

    public function test_cancelled_deduction_does_not_block_creation_of_same_type(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-deductions', 'delete-deductions']);

        $employeeUser = $this->makeStaffUser($company, 'Deduction Recreate Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-DED-RECREATE-001');

        $deductionType = DeductionType::query()->create([
            'name' => 'Deduction Recreate Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $deduction = Deduction::query()->create([
            'employee_id' => $employeeUser->id,
            'deduction_type_id' => $deductionType->id,
            'type' => 'fixed',
            'amount' => 250,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->delete(route('hrm.deductions.destroy', [$deduction->id, $employee->id]), [
            'cancellation_reason' => 'Registo inicial cancelado para novo valor.',
        ]);

        $response = $this->actingAs($company)->post(route('hrm.deductions.store'), [
            'employee_id' => $employee->id,
            'deduction_type_id' => $deductionType->id,
            'type' => 'fixed',
            'amount' => 300,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseCount('deductions', 2);
        $this->assertDatabaseHas('deductions', [
            'employee_id' => $employeeUser->id,
            'deduction_type_id' => $deductionType->id,
            'amount' => 300,
            'is_cancelled' => false,
        ]);
    }

    public function test_cancelled_loan_does_not_block_creation_of_same_type(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['create-loans', 'delete-loans']);

        $employeeUser = $this->makeStaffUser($company, 'Loan Recreate Worker');
        $employee = $this->attachEmployeeProfile($company, $employeeUser, 'EMP-LOAN-RECREATE-001');

        $loanType = LoanType::query()->create([
            'name' => 'Loan Recreate Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $loan = Loan::query()->create([
            'title' => 'Loan original',
            'employee_id' => $employeeUser->id,
            'loan_type_id' => $loanType->id,
            'type' => 'fixed',
            'amount' => 1300,
            'start_date' => '2026-06-01',
            'end_date' => '2026-07-01',
            'reason' => 'Initial',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company)->delete(route('hrm.loans.destroy', [$loan->id, $employee->id]), [
            'cancellation_reason' => 'Registo inicial cancelado para novo valor.',
        ]);

        $response = $this->actingAs($company)->post(route('hrm.loans.store'), [
            'employee_id' => $employee->id,
            'title' => 'Loan replacement',
            'loan_type_id' => $loanType->id,
            'type' => 'fixed',
            'amount' => 1500,
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-15',
            'reason' => 'Replacement',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseCount('loans', 2);
        $this->assertDatabaseHas('loans', [
            'employee_id' => $employeeUser->id,
            'loan_type_id' => $loanType->id,
            'title' => 'Loan replacement',
            'is_cancelled' => false,
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
