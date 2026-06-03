<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Hrm\Models\Allowance;
use Workdo\Hrm\Models\AllowanceType;
use Workdo\Hrm\Models\AnnualLeavePlan;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeDependent;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Resignation;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\Warning;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_create_update_delete_are_logged(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeClient($company);
        $warehouse = $this->makeWarehouse($company);

        $this->actingAs($company);

        $invoice = SalesInvoice::create([
            'invoice_number' => 'SI-AUDIT-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'draft',
            'type' => 'product',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $invoice->update([
            'status' => 'posted',
        ]);

        $invoiceId = $invoice->id;
        $invoice->delete();

        $entries = AuditTrail::query()
            ->where('auditable_type', SalesInvoice::class)
            ->where('auditable_id', $invoiceId)
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(['created', 'updated', 'deleted'], $entries->pluck('event')->all());

        $this->assertSame($company->id, $entries[0]->company_id);
        $this->assertSame($company->id, $entries[0]->user_id);
        $this->assertSame('draft', $entries[0]->new_values['status']);

        $this->assertSame('draft', $entries[1]->old_values['status']);
        $this->assertSame('posted', $entries[1]->new_values['status']);
        $this->assertSame(['status' => 'posted'], $entries[1]->changes);

        $this->assertSame('posted', $entries[2]->old_values['status']);
    }

    public function test_payroll_create_is_logged_when_hrm_module_is_available(): void
    {
        $payrollModelClass = 'Workdo\\Hrm\\Models\\Payroll';

        if (! class_exists($payrollModelClass)) {
            $this->markTestSkipped('HRM payroll model is not available.');
        }

        $company = $this->makeCompany();
        $this->actingAs($company);

        $payroll = $payrollModelClass::create([
            'title' => 'Payroll Janeiro 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => now()->startOfMonth()->toDateString(),
            'pay_period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->toDateString(),
            'status' => 'draft',
            'is_payroll_paid' => 'unpaid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = AuditTrail::query()
            ->where('auditable_type', $payrollModelClass)
            ->where('auditable_id', $payroll->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('created', $entry->event);
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame($company->id, $entry->user_id);
        $this->assertSame('Payroll Janeiro 2026', $entry->new_values['title']);
    }

    public function test_warning_cancellation_update_is_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(Warning::class)) {
            $this->markTestSkipped('HRM warning model is not available.');
        }

        $company = $this->makeCompany();
        $employee = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $this->actingAs($company);

        $warning = Warning::query()->create([
            'employee_id' => $employee->id,
            'subject' => 'Teste de auditoria disciplinar',
            'severity' => 'Minor',
            'warning_date' => now()->toDateString(),
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $warning->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $company->id,
            'cancellation_reason' => 'Registo duplicado.',
        ]);

        $entries = AuditTrail::query()
            ->where('auditable_type', Warning::class)
            ->where('auditable_id', $warning->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame(['created', 'updated'], $entries->pluck('event')->all());
        $this->assertSame($company->id, $entries[0]->company_id);
        $this->assertSame($company->id, $entries[1]->user_id);
        $this->assertTrue((bool) ($entries[1]->new_values['is_cancelled'] ?? false));
        $this->assertSame('Registo duplicado.', $entries[1]->new_values['cancellation_reason'] ?? null);
    }

    public function test_allowance_cancellation_update_is_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(Allowance::class) || ! class_exists(AllowanceType::class)) {
            $this->markTestSkipped('HRM allowance models are not available.');
        }

        $company = $this->makeCompany();
        $employeeUser = $this->makeStaffUser($company, 'Audit Allowance Worker');
        $this->makeEmployeeProfile($company, $employeeUser, 'EMP-AUD-ALLOW-001');

        $allowanceType = AllowanceType::query()->create([
            'name' => 'Allowance Audit Type',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);

        $allowance = Allowance::query()->create([
            'employee_id' => $employeeUser->id,
            'allowance_type_id' => $allowanceType->id,
            'type' => 'fixed',
            'amount' => 700,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $allowance->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $company->id,
            'cancellation_reason' => 'Allowance substituido por novo registo.',
        ]);

        $entries = AuditTrail::query()
            ->where('auditable_type', Allowance::class)
            ->where('auditable_id', $allowance->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame(['created', 'updated'], $entries->pluck('event')->all());
        $this->assertSame($company->id, $entries[1]->company_id);
        $this->assertSame($company->id, $entries[1]->user_id);
        $this->assertTrue((bool) ($entries[1]->new_values['is_cancelled'] ?? false));
        $this->assertSame('Allowance substituido por novo registo.', $entries[1]->new_values['cancellation_reason'] ?? null);
    }

    public function test_employee_dependent_cancellation_update_is_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(EmployeeDependent::class)) {
            $this->markTestSkipped('HRM employee dependent model is not available.');
        }

        $company = $this->makeCompany();
        $employeeUser = $this->makeStaffUser($company, 'Audit Dependent Worker');
        $employee = $this->makeEmployeeProfile($company, $employeeUser, 'EMP-AUD-DEP-001');

        $this->actingAs($company);

        $dependent = EmployeeDependent::query()->create([
            'employee_id' => $employee->id,
            'full_name' => 'Dependent Audit',
            'relationship' => 'child',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'is_student' => true,
            'is_tax_eligible' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $dependent->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $company->id,
            'cancellation_reason' => 'Dependente desatualizado e substituido.',
        ]);

        $entries = AuditTrail::query()
            ->where('auditable_type', EmployeeDependent::class)
            ->where('auditable_id', $dependent->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame(['created', 'updated'], $entries->pluck('event')->all());
        $this->assertSame($company->id, $entries[1]->company_id);
        $this->assertSame($company->id, $entries[1]->user_id);
        $this->assertTrue((bool) ($entries[1]->new_values['is_cancelled'] ?? false));
        $this->assertSame('Dependente desatualizado e substituido.', $entries[1]->new_values['cancellation_reason'] ?? null);
    }

    public function test_annual_leave_plan_cancellation_update_is_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(AnnualLeavePlan::class) || ! class_exists(LeaveType::class)) {
            $this->markTestSkipped('HRM annual leave plan models are not available.');
        }

        $company = $this->makeCompany();
        $employeeUser = $this->makeStaffUser($company, 'Audit Annual Leave Worker');
        $this->makeEmployeeProfile($company, $employeeUser, 'EMP-AUD-ALP-001');

        $leaveType = LeaveType::query()->create([
            'name' => 'Férias anuais',
            'description' => 'Tipo de licença para auditoria',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);

        $plan = AnnualLeavePlan::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $leaveType->id,
            'leave_year' => (int) now()->year,
            'planned_start_date' => now()->addDays(10)->toDateString(),
            'planned_end_date' => now()->addDays(14)->toDateString(),
            'planned_days' => 5,
            'status' => AnnualLeavePlan::STATUS_PENDING_MANAGER,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $plan->update([
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'cancelled_by' => $company->id,
            'cancellation_reason' => 'Plano de férias substituido por ajuste operacional.',
        ]);

        $entries = AuditTrail::query()
            ->where('auditable_type', AnnualLeavePlan::class)
            ->where('auditable_id', $plan->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame(['created', 'updated'], $entries->pluck('event')->all());
        $this->assertSame($company->id, $entries[1]->company_id);
        $this->assertSame($company->id, $entries[1]->user_id);
        $this->assertTrue((bool) ($entries[1]->new_values['is_cancelled'] ?? false));
        $this->assertSame('Plano de férias substituido por ajuste operacional.', $entries[1]->new_values['cancellation_reason'] ?? null);
    }

    public function test_attendance_create_and_update_are_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(Attendance::class)) {
            $this->markTestSkipped('HRM attendance model is not available.');
        }

        $company = $this->makeCompany();
        $employeeUser = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $shift = Shift::query()->create([
            'shift_name' => 'Audit Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'break_start_time' => '12:00:00',
            'break_end_time' => '13:00:00',
            'is_night_shift' => false,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);

        $attendance = Attendance::query()->create([
            'employee_id' => $employeeUser->id,
            'shift_id' => $shift->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->copy()->setTime(8, 0),
            'clock_out' => now()->copy()->setTime(17, 0),
            'status' => 'present',
            'notes' => 'Attendance audit baseline',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $attendance->update([
            'notes' => 'Attendance audit updated',
        ]);

        $entries = AuditTrail::query()
            ->where('auditable_type', Attendance::class)
            ->where('auditable_id', $attendance->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame(['created', 'updated'], $entries->pluck('event')->all());
        $this->assertSame($company->id, $entries[0]->company_id);
        $this->assertSame($company->id, $entries[1]->user_id);
        $this->assertSame('Attendance audit updated', $entries[1]->new_values['notes'] ?? null);
    }

    public function test_resignation_create_is_logged_when_hrm_module_is_available(): void
    {
        if (! class_exists(Resignation::class)) {
            $this->markTestSkipped('HRM resignation model is not available.');
        }

        $company = $this->makeCompany();
        $employeeUser = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $this->actingAs($company);

        $resignation = Resignation::query()->create([
            'employee_id' => $employeeUser->id,
            'last_working_date' => now()->addDays(15)->toDateString(),
            'reason' => 'Personal reason',
            'description' => 'Audit resignation log',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = AuditTrail::query()
            ->where('auditable_type', Resignation::class)
            ->where('auditable_id', $resignation->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('created', $entry->event);
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame($company->id, $entry->user_id);
        $this->assertSame('Personal reason', $entry->new_values['reason'] ?? null);
    }

    public function test_sensitive_hr_fields_are_masked_in_audit_trail(): void
    {
        if (! class_exists(EmployeeForeignWorkerProfile::class)) {
            $this->markTestSkipped('HRM foreign worker profile model is not available.');
        }

        $company = $this->makeCompany();
        $staffUser = User::factory()->create([
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $employee = Employee::query()->create([
            'employee_id' => 'EMP-AUD-MASK-001',
            'user_id' => $staffUser->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400998877',
            'account_number' => '002233445566',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $this->actingAs($company);

        $profile = EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'passport_number' => 'P123456789',
            'work_authorization_number' => 'AUT-778899',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $entry = AuditTrail::query()
            ->where('auditable_type', EmployeeForeignWorkerProfile::class)
            ->where('auditable_id', $profile->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('created', $entry->event);
        $this->assertSame('******6789', $entry->new_values['passport_number'] ?? null);
        $this->assertSame('******8899', $entry->new_values['work_authorization_number'] ?? null);
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

    private function makeClient(User $company): User
    {
        return User::factory()->create([
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }

    private function makeWarehouse(User $company): Warehouse
    {
        return Warehouse::create([
            'name' => 'Audit Warehouse',
            'address' => 'Address',
            'city' => 'Maputo',
            'zip_code' => '1100',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
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

    private function makeEmployeeProfile(User $company, User $staff, string $employeeCode): Employee
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
