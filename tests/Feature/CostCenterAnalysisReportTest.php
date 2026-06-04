<?php

namespace Tests\Feature;

use App\Http\Middleware\PlanModuleCheck;
use App\Models\CostCenter;
use App\Models\User;
use App\Services\PayrollCostCenterAllocatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Workdo\Account\Helpers\AccountUtility;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;
use Workdo\Account\Services\ReportService;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Designation;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\PayrollEntry;

class CostCenterAnalysisReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PlanModuleCheck::class);
    }

    public function test_cost_center_analysis_report_detects_allocations_and_missing_required_dimensions(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        AccountUtility::defaultdata($company->id);

        $costCenter = CostCenter::query()->create([
            'company_id' => $company->id,
            'code' => 'CC-OPS-01',
            'name' => 'Operações Maputo',
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $missingCostCenterAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '5100')
            ->firstOrFail();
        $missingCostCenterAccount->update(['cost_center_required' => true]);

        $assignedJournal = JournalEntry::query()->create([
            'journal_date' => '2026-05-10',
            'entry_type' => 'automatic',
            'reference_type' => 'expense',
            'description' => 'Despesa atribuída',
            'total_debit' => 1200,
            'total_credit' => 1200,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $assignedExpenseAccount = $missingCostCenterAccount;
        $bankAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        JournalEntryItem::query()->create([
            'journal_entry_id' => $assignedJournal->id,
            'account_id' => $assignedExpenseAccount->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Despesa operacional com centro de custo',
            'debit_amount' => 1200,
            'credit_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        JournalEntryItem::query()->create([
            'journal_entry_id' => $assignedJournal->id,
            'account_id' => $bankAccount->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Pagamento operacional com centro de custo',
            'debit_amount' => 0,
            'credit_amount' => 1200,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $missingJournal = JournalEntry::query()->create([
            'journal_date' => '2026-05-12',
            'entry_type' => 'automatic',
            'reference_type' => 'adjustment',
            'description' => 'Ajuste sem centro de custo',
            'total_debit' => 500,
            'total_credit' => 500,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        JournalEntryItem::query()->create([
            'journal_entry_id' => $missingJournal->id,
            'account_id' => $assignedExpenseAccount->id,
            'description' => 'Despesa obrigatória sem centro de custo',
            'debit_amount' => 500,
            'credit_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        JournalEntryItem::query()->create([
            'journal_entry_id' => $missingJournal->id,
            'account_id' => $bankAccount->id,
            'description' => 'Contrapartida sem centro de custo',
            'debit_amount' => 0,
            'credit_amount' => 500,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $branch = Branch::query()->create([
            'branch_name' => 'Maputo',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $department = Department::query()->create([
            'branch_id' => $branch->id,
            'department_name' => 'Financeiro',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $designation = Designation::query()->create([
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_name' => 'Analista',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employeeUser = $this->makeStaff($company, 'Funcionario Centro Custo');

        Employee::query()->create([
            'employee_id' => 'EMP-CC-001',
            'user_id' => $employeeUser->id,
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'basic_salary' => 30000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        app(PayrollCostCenterAllocatorService::class)->saveConfiguration($company->id, [
            'mode' => 'configured',
            'mappings' => [
                'employee' => [],
                'department' => [
                    (string) $department->id => $costCenter->id,
                ],
                'branch' => [],
            ],
        ]);

        $payroll = Payroll::query()->create([
            'title' => 'Payroll Maio 2026',
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
            'irps_amount' => 1200,
            'inss_employee_amount' => 960,
            'inss_employer_amount' => 1280,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        if (
            class_exists('\\Workdo\\Timesheet\\Models\\Timesheet')
            && class_exists('\\Workdo\\Taskly\\Models\\Project')
        ) {
            $client = User::factory()->create([
                'name' => 'Client Cost Center',
                'type' => 'client',
                'created_by' => $company->id,
                'creator_id' => $company->id,
            ]);

            $projectClass = '\\Workdo\\Taskly\\Models\\Project';
            $project = $projectClass::query()->create([
                'name' => 'Projeto Operacional',
                'description' => 'Projeto usado para validar centro de custo',
                'status' => 'Ongoing',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
            $project->clients()->attach($client->id);

            $timesheetClass = '\\Workdo\\Timesheet\\Models\\Timesheet';
            $timesheetClass::query()->create([
                'user_id' => $employeeUser->id,
                'project_id' => $project->id,
                'date' => '2026-05-15',
                'hours' => 8,
                'minutes' => 0,
                'type' => 'project',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
        }

        $report = app(ReportService::class)->getMozambiqueCostCenterAnalysis([
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'reference_period' => '2026-05',
        ]);

        $this->assertSame('2026-05-01', $report['from_date']);
        $this->assertSame('2026-05-31', $report['to_date']);
        $this->assertSame('2026-05', $report['reference_period']);
        $this->assertSame(4, $report['summary']['journal_lines']);
        $this->assertSame(2, $report['summary']['assigned_lines']);
        $this->assertSame(1, $report['summary']['required_missing_lines']);
        $this->assertSame(1, $report['summary']['unassigned_lines']);
        $this->assertSame(1, $report['summary']['cost_centers']);
        $this->assertSame(1, $report['summary']['payroll_rows']);
        $this->assertSame(1, $report['summary']['payroll_cost_centers']);
        $this->assertSame(1, $report['summary']['payroll_departments']);
        $this->assertSame(1, $report['summary']['payroll_branches']);

        $this->assertCount(1, $report['cost_centers']);
        $this->assertSame('CC-OPS-01', $report['cost_centers'][0]['cost_center_code']);
        $this->assertSame(2, $report['cost_centers'][0]['line_count']);

        $this->assertCount(1, $report['required_missing_lines']);
        $this->assertSame('5100', $report['required_missing_lines'][0]['account_code']);
        $this->assertSame('required_missing', $report['required_missing_lines'][0]['allocation_status']);

        $this->assertCount(1, $report['payroll_allocations']);
        $this->assertSame('Financeiro', $report['payroll_allocations'][0]['department']);
        $this->assertSame('Maputo', $report['payroll_allocations'][0]['branch']);
        $this->assertSame('CC-OPS-01', $report['payroll_allocations'][0]['cost_center_code']);
    }

    public function test_cost_center_analysis_export_returns_csv_summary(): void
    {
        $company = $this->makeCompany();
        $this->grantPermissions($company, ['manage-account-reports']);
        $this->actingAs($company);

        AccountUtility::defaultdata($company->id);

        $costCenter = CostCenter::query()->create([
            'company_id' => $company->id,
            'code' => 'CC-CSV-01',
            'name' => 'Centro CSV',
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '5100')
            ->update(['cost_center_required' => true]);

        $journal = JournalEntry::query()->create([
            'journal_date' => '2026-05-20',
            'entry_type' => 'automatic',
            'reference_type' => 'expense',
            'description' => 'Linha CSV',
            'total_debit' => 300,
            'total_credit' => 300,
            'status' => 'posted',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $expenseAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '5100')
            ->firstOrFail();
        $bankAccount = ChartOfAccount::query()
            ->where('created_by', $company->id)
            ->where('account_code', '1030')
            ->firstOrFail();

        JournalEntryItem::query()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $expenseAccount->id,
            'cost_center_id' => $costCenter->id,
            'description' => 'Linha atribuída',
            'debit_amount' => 300,
            'credit_amount' => 0,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        JournalEntryItem::query()->create([
            'journal_entry_id' => $journal->id,
            'account_id' => $bankAccount->id,
            'description' => 'Contrapartida',
            'debit_amount' => 0,
            'credit_amount' => 300,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $response = $this->actingAs($company)->get(route('account.reports.mozambique-cost-center-analysis.export', [
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'reference_period' => '2026-05',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('summary', false);
        $response->assertSee('cost_center', false);
        $response->assertSee('CC-CSV-01', false);
    }

    private function makeCompany(): User
    {
        return User::factory()->create([
            'type' => 'company',
            'created_by' => null,
            'email_verified_at' => now(),
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

    private function grantPermissions(User $user, array $permissions): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'web',
                ],
                [
                    'add_on' => 'general',
                    'module' => 'reports',
                    'label' => $permission,
                ]
            );
        }

        $user->givePermissionTo($permissions);
        $user->refresh();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
