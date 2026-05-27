<?php

namespace Tests\Feature;

use App\Models\MozInssRate;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use App\Models\FiscalCalendarEvent;
use App\Models\User;
use App\Services\MozambiqueHrComplianceDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Workdo\Contract\Models\Contract;
use Workdo\Contract\Models\ContractType;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;
use Workdo\Hrm\Models\EmployeeSocialSecurityProfile;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Models\Termination;
use Workdo\Hrm\Models\Warning;
use Workdo\Hrm\Models\Payroll;

class MozambiqueHrComplianceDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_reports_key_hr_compliance_risks(): void
    {
        $company = $this->makeCompany();
        $employeeUserA = $this->makeEmployeeUser($company, 'Worker A');
        $employeeUserB = $this->makeEmployeeUser($company, 'Worker B');

        $employeeA = Employee::query()->create([
            'employee_id' => 'CMP-EMP-001',
            'user_id' => $employeeUserA->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '',
            'basic_salary' => 10000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $employeeB = Employee::query()->create([
            'employee_id' => 'CMP-EMP-002',
            'user_id' => $employeeUserB->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400999123',
            'basic_salary' => 11000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeSocialSecurityProfile::query()->create([
            'employee_id' => $employeeB->id,
            'inss_number' => 'INSS-7788',
            'registration_status' => 'registered',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employeeA->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'passport_expires_at' => now()->subDay()->toDateString(),
            'hiring_regime' => 'quota',
            'cessation_effective_date' => now()->subDays(9)->toDateString(),
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeProbationProfile::query()->create([
            'employee_id' => $employeeA->id,
            'probation_category' => 'general',
            'starts_at' => now()->subDays(70)->toDateString(),
            'expected_end_at' => now()->subDays(2)->toDateString(),
            'legal_max_days' => 60,
            'evaluation_status' => 'pending',
            'decision_status' => 'ongoing',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $contractType = ContractType::query()->create([
            'name' => 'Labour',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Contract::query()->create([
            'subject' => 'Contrato a Prazo',
            'user_id' => $employeeUserA->id,
            'value' => 15000,
            'type_id' => $contractType->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'pending',
            'source_type' => 'contract',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);

        $this->assertSame(2, $snapshot['metrics']['total_workers']);
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'employees_without_nuit'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'employees_without_inss'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'foreign_documents_expired'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'foreign_cessation_notification_overdue'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'probation_overdue'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'fixed_term_without_justification'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'missing_active_irps_table'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'missing_active_inss_rate'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'missing_active_minimum_wage'));
    }

    public function test_snapshot_clears_configuration_alerts_when_active_tables_exist(): void
    {
        $company = $this->makeCompany();

        MozIrpsTable::query()->create([
            'name' => 'IRPS 2026',
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        MozInssRate::query()->create([
            'employee_rate' => 3,
            'employer_rate' => 4,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        MozMinimumWage::query()->create([
            'sector_code' => 'GENERAL',
            'sector_name' => 'General',
            'monthly_amount' => 5000,
            'effective_from' => now()->subMonth()->toDateString(),
            'is_active' => true,
            'created_by' => $company->id,
        ]);

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);

        $this->assertSame(0, $this->countByKey($snapshot['items'], 'missing_active_irps_table'));
        $this->assertSame(0, $this->countByKey($snapshot['items'], 'missing_active_inss_rate'));
        $this->assertSame(0, $this->countByKey($snapshot['items'], 'missing_active_minimum_wage'));
    }

    public function test_snapshot_reports_disciplinary_harassment_and_offboarding_alerts(): void
    {
        $company = $this->makeCompany();
        $employeeUser = $this->makeEmployeeUser($company, 'Worker Legal');

        $employee = Employee::query()->create([
            'employee_id' => 'CMP-EMP-LEGAL-001',
            'user_id' => $employeeUser->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400123999',
            'basic_salary' => 12000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Warning::query()->create([
            'employee_id' => $employeeUser->id,
            'warning_by' => $company->id,
            'subject' => 'Atraso recorrente',
            'severity' => 'Moderate',
            'warning_date' => now()->subDays(10)->toDateString(),
            'status' => 'pending',
            'response_deadline_at' => now()->subDay()->toDateString(),
            'decision_deadline_at' => now()->subDay()->toDateString(),
            'worker_refused_note_of_culpa' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Complaint::query()->create([
            'employee_id' => $employeeUser->id,
            'against_employee_id' => $company->id,
            'subject' => 'Queixa de conduta',
            'description' => 'Conteudo de teste',
            'complaint_date' => now()->subDays(2)->toDateString(),
            'status' => 'in review',
            'is_harassment_report' => true,
            'is_confidential' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employee->id,
            'is_foreign_worker' => true,
            'nationality' => 'ZA',
            'residency_status' => 'non_resident',
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Termination::query()->create([
            'employee_id' => $employeeUser->id,
            'reason' => 'Fim de contrato',
            'notice_date' => now()->subDays(15)->toDateString(),
            'termination_date' => now()->subDays(7)->toDateString(),
            'status' => 'approved',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $legalLeaveType = LeaveType::query()->create([
            'name' => 'Baixa Médica',
            'legal_code' => 'sick_leave',
            'description' => 'Teste',
            'max_days_per_year' => 30,
            'is_paid' => false,
            'requires_supporting_document' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $legalLeaveType->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'total_days' => 3,
            'compensated_days' => 0,
            'effective_rest_days' => 3,
            'reason' => 'Teste sem documento',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $annualLeaveType = LeaveType::query()->create([
            'name' => 'Férias Anuais',
            'legal_code' => 'annual',
            'description' => 'Teste',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'allow_cash_out' => true,
            'min_effective_rest_days' => 6,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $maternityLeaveType = LeaveType::query()->create([
            'name' => 'Licença Maternidade',
            'legal_code' => 'maternity',
            'description' => 'Teste',
            'max_days_per_year' => 90,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $annualLeaveType->id,
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'total_days' => 10,
            'compensated_days' => 5,
            'effective_rest_days' => 5,
            'reason' => 'Teste cash out',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveApplication::query()->create([
            'employee_id' => $employeeUser->id,
            'leave_type_id' => $maternityLeaveType->id,
            'start_date' => now()->addDays(14)->toDateString(),
            'end_date' => now()->addDays(103)->toDateString(),
            'total_days' => 90,
            'compensated_days' => 0,
            'effective_rest_days' => 90,
            'reason' => 'Teste ref date',
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Overtime::query()->create([
            'title' => 'Horas extra dia 1',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 5,
            'rate' => 100,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
            'status' => 'active',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Overtime::query()->create([
            'title' => 'Horas extra dia 2',
            'employee_id' => $employeeUser->id,
            'total_days' => 1,
            'hours' => 12,
            'rate' => 100,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'status' => 'active',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $shift = Shift::query()->create([
            'shift_name' => 'Turno Padrão',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        for ($i = 0; $i < 7; $i++) {
            $day = now()->subDays($i);
            Attendance::query()->create([
                'employee_id' => $employeeUser->id,
                'shift_id' => $shift->id,
                'date' => $day->toDateString(),
                'clock_in' => $day->copy()->setTime(8, 0),
                'clock_out' => $day->copy()->setTime(17, 0),
                'status' => 'present',
                'creator_id' => $company->id,
                'created_by' => $company->id,
            ]);
        }

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);

        $this->assertSame(1, $this->countByKey($snapshot['items'], 'disciplinary_response_overdue'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'disciplinary_decision_overdue'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'disciplinary_refusal_without_witnesses'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'harassment_reports_pending'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'harassment_reports_without_owner'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'offboarding_checklist_pending'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'foreign_offboarding_migration_pending'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'leave_missing_supporting_document'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'legal_leave_missing_reference_date'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'leave_cash_out_below_min_rest'));
        $this->assertSame(2, $this->countByKey($snapshot['items'], 'overtime_daily_limit_breaches'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'overtime_weekly_limit_breaches'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'weekly_rest_breach_risk'));
    }

    public function test_snapshot_reports_overdue_inss_and_irps_submissions_from_completed_payrolls(): void
    {
        $company = $this->makeCompany();
        $periodStart = now()->subMonths(3)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        Payroll::query()->create([
            'title' => 'Payroll legal obligations',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => $periodStart->toDateString(),
            'pay_period_end' => $periodEnd->toDateString(),
            'pay_date' => $periodEnd->toDateString(),
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_irps' => 5500,
            'total_inss_employee' => 1800,
            'total_inss_employer' => 2400,
            'total_gross_pay' => 90000,
            'employee_count' => 4,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);

        $this->assertSame(1, $this->countByKey($snapshot['items'], 'payroll_inss_submission_overdue'));
        $this->assertSame(1, $this->countByKey($snapshot['items'], 'payroll_irps_submission_overdue'));
        $this->assertSame(1, (int) data_get($snapshot, 'payroll_obligations.totals.overdue_inss'));
        $this->assertSame(1, (int) data_get($snapshot, 'payroll_obligations.totals.overdue_irps'));
    }

    public function test_snapshot_respects_completed_fiscal_events_for_inss_and_irps_submissions(): void
    {
        $company = $this->makeCompany();
        $periodStart = now()->subMonths(3)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $referencePeriod = $periodStart->format('Y-m');

        Payroll::query()->create([
            'title' => 'Payroll completed submissions',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => $periodStart->toDateString(),
            'pay_period_end' => $periodEnd->toDateString(),
            'pay_date' => $periodEnd->toDateString(),
            'status' => 'completed',
            'is_payroll_paid' => 'paid',
            'total_irps' => 2200,
            'total_inss_employee' => 900,
            'total_inss_employer' => 1200,
            'total_gross_pay' => 35000,
            'employee_count' => 2,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        FiscalCalendarEvent::query()->create([
            'company_id' => $company->id,
            'code' => "INSS-{$referencePeriod}",
            'title' => 'INSS submission',
            'obligation_type' => 'inss',
            'due_date' => $periodEnd->copy()->addMonthNoOverflow()->day(10)->toDateString(),
            'reference_period' => $referencePeriod,
            'status' => 'completed',
            'completed_date' => now()->toDateString(),
            'created_by' => $company->id,
        ]);

        FiscalCalendarEvent::query()->create([
            'company_id' => $company->id,
            'code' => "IRPS-{$referencePeriod}",
            'title' => 'IRPS submission',
            'obligation_type' => 'irps',
            'due_date' => $periodEnd->copy()->addMonthNoOverflow()->day(20)->toDateString(),
            'reference_period' => $referencePeriod,
            'status' => 'completed',
            'completed_date' => now()->toDateString(),
            'created_by' => $company->id,
        ]);

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);

        $this->assertSame(0, $this->countByKey($snapshot['items'], 'payroll_inss_submission_overdue'));
        $this->assertSame(0, $this->countByKey($snapshot['items'], 'payroll_irps_submission_overdue'));
        $this->assertSame(1, (int) data_get($snapshot, 'payroll_obligations.totals.completed_inss'));
        $this->assertSame(1, (int) data_get($snapshot, 'payroll_obligations.totals.completed_irps'));
    }

    public function test_snapshot_contains_rf096_compliance_panel_indicators(): void
    {
        $company = $this->makeCompany();
        $employeeUserA = $this->makeEmployeeUser($company, 'Panel Worker A');
        $employeeUserB = $this->makeEmployeeUser($company, 'Panel Worker B');

        $employeeA = Employee::query()->create([
            'employee_id' => 'CMP-PANEL-001',
            'user_id' => $employeeUserA->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '',
            'date_of_joining' => now()->subYears(2)->toDateString(),
            'basic_salary' => 14000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Employee::query()->create([
            'employee_id' => 'CMP-PANEL-002',
            'user_id' => $employeeUserB->id,
            'employment_type' => 'GENERAL',
            'tax_payer_id' => '400556677',
            'date_of_joining' => now()->subYears(2)->toDateString(),
            'basic_salary' => 15000,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        EmployeeForeignWorkerProfile::query()->create([
            'employee_id' => $employeeA->id,
            'is_foreign_worker' => true,
            'nationality' => 'PT',
            'residency_status' => 'non_resident',
            'passport_expires_at' => now()->subDay()->toDateString(),
            'hiring_regime' => 'quota',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $contractType = ContractType::query()->create([
            'name' => 'Labour Panel',
            'is_active' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Contract::query()->create([
            'subject' => 'Contrato expirado',
            'user_id' => $employeeUserA->id,
            'value' => 18000,
            'type_id' => $contractType->id,
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(),
            'status' => 'pending',
            'source_type' => 'contract',
            'is_labour_contract' => true,
            'legal_contract_type' => 'fixed_term',
            'fixed_term_justification' => 'Projeto',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        Warning::query()->create([
            'employee_id' => $employeeUserA->id,
            'warning_by' => $company->id,
            'subject' => 'Ocorrencia pendente',
            'severity' => 'Moderate',
            'warning_date' => now()->subDays(5)->toDateString(),
            'status' => 'pending',
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        LeaveType::query()->create([
            'name' => 'Ferias Anuais',
            'legal_code' => 'annual',
            'description' => 'Painel',
            'max_days_per_year' => 30,
            'is_paid' => true,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $periodStart = now()->subMonths(3)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        Payroll::query()->create([
            'title' => 'Payroll panel obligations',
            'payroll_frequency' => 'monthly',
            'pay_period_start' => $periodStart->toDateString(),
            'pay_period_end' => $periodEnd->toDateString(),
            'pay_date' => $periodEnd->toDateString(),
            'status' => 'completed',
            'is_payroll_paid' => 'unpaid',
            'total_irps' => 3000,
            'total_inss_employee' => 1200,
            'total_inss_employer' => 1600,
            'total_gross_pay' => 50000,
            'employee_count' => 2,
            'creator_id' => $company->id,
            'created_by' => $company->id,
        ]);

        $snapshot = app(MozambiqueHrComplianceDashboardService::class)->snapshot($company->id);
        $indicators = (array) data_get($snapshot, 'compliance_panel.indicators', []);

        $this->assertSame(2, $this->panelCountByKey($indicators, 'workers_without_signed_contract'));
        $this->assertSame(1, $this->panelCountByKey($indicators, 'workers_without_nuit'));
        $this->assertSame(2, $this->panelCountByKey($indicators, 'workers_without_inss'));
        $this->assertSame(1, $this->panelCountByKey($indicators, 'labour_contracts_expired'));
        $this->assertSame(1, $this->panelCountByKey($indicators, 'foreign_documents_expired'));
        $this->assertSame(2, $this->panelCountByKey($indicators, 'accumulated_annual_leave_risk'));
        $this->assertSame(1, $this->panelCountByKey($indicators, 'disciplinary_cases_pending'));
        $this->assertSame(2, $this->panelCountByKey($indicators, 'payroll_fiscal_obligations_pending'));
        $this->assertSame(1, $this->panelCountByKey($indicators, 'foreign_workers_at_compliance_risk'));
        $this->assertSame(9, (int) data_get($snapshot, 'compliance_panel.metrics.total_indicators'));
    }

    private function countByKey(array $items, string $key): int
    {
        foreach ($items as $item) {
            if (($item['key'] ?? '') === $key) {
                return (int) ($item['count'] ?? 0);
            }
        }

        return 0;
    }

    private function panelCountByKey(array $indicators, string $key): int
    {
        foreach ($indicators as $indicator) {
            if (($indicator['key'] ?? '') === $key) {
                return (int) ($indicator['count'] ?? 0);
            }
        }

        return 0;
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

    private function makeEmployeeUser(User $company, string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'type' => 'staff',
            'created_by' => $company->id,
            'creator_id' => $company->id,
        ]);
    }
}
