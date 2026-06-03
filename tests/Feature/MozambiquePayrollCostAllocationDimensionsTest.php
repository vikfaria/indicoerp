<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MozambiquePayrollAccountingExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class MozambiquePayrollCostAllocationDimensionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_allocation_dataset_includes_project_and_client_dimensions_from_timesheets(): void
    {
        if (!class_exists('\\Workdo\\Timesheet\\Models\\Timesheet')
            || !class_exists('\\Workdo\\Taskly\\Models\\Project')
            || !Schema::hasTable('timesheets')
            || !Schema::hasTable('projects')
            || !Schema::hasTable('project_clients')) {
            $this->markTestSkipped('Timesheet/Taskly tables are not available in this environment.');
        }

        $company = $this->makeCompany();
        $employeeUser = $this->makeStaff($company, 'Payroll Employee');
        $clientUser = User::factory()->create([
            'name' => 'Main Client',
            'type' => 'client',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);

        $branch = Branch::query()->create([
            'branch_name' => 'HQ',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        $department = Department::query()->create([
            'branch_id' => $branch->id,
            'department_name' => 'Finance',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        $designation = Designation::query()->create([
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_name' => 'Accountant',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'PAY-001',
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_type' => 'GENERAL',
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $projectClass = '\\Workdo\\Taskly\\Models\\Project';
        $project = $projectClass::query()->create([
            'name' => 'ERP Rollout',
            'description' => 'Cost allocation test project',
            'status' => 'Ongoing',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        $project->clients()->attach($clientUser->id);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll May 2026',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => '2026-05-01',
            'pay_period_end' => '2026-05-31',
            'pay_date' => '2026-05-31',
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        PayrollEntry::query()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employeeUser->id,
            'gross_pay' => 30000,
            'net_pay' => 26000,
            'total_allowances' => 1000,
            'total_manual_overtimes' => 500,
            'total_deductions' => 4000,
            'total_loans' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $timesheetClass = '\\Workdo\\Timesheet\\Models\\Timesheet';
        $timesheetClass::query()->create([
            'user_id' => $employeeUser->id,
            'project_id' => $project->id,
            'date' => '2026-05-10',
            'hours' => 8,
            'minutes' => 0,
            'type' => 'project',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);
        $timesheetClass::query()->create([
            'user_id' => $employeeUser->id,
            'project_id' => $project->id,
            'date' => '2026-05-12',
            'hours' => 6,
            'minutes' => 30,
            'type' => 'project',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $dataset = app(MozambiquePayrollAccountingExportService::class)->buildCostAllocationDataset($company->id, '2026-05');
        $row = collect($dataset['rows'])->first();

        $this->assertNotNull($row);
        $this->assertSame('ERP Rollout', (string) ($row['project_name'] ?? ''));
        $this->assertSame('Main Client', (string) ($row['client_name'] ?? ''));
        $this->assertSame('timesheet', (string) ($row['allocation_source'] ?? ''));
        $this->assertGreaterThan(0, (int) ($row['allocation_minutes'] ?? 0));
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

    private function makeStaff(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }
}
