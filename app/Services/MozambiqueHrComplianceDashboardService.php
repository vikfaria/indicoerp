<?php

namespace App\Services;

use App\Models\MozInssRate;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Carbon\Carbon;
use Workdo\Contract\Models\Contract;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Complaint;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\EmployeeForeignWorkerProfile;
use Workdo\Hrm\Models\EmployeeProbationProfile;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\Termination;
use Workdo\Hrm\Models\Warning;

class MozambiqueHrComplianceDashboardService
{
    public function __construct(
        private readonly MozambiqueForeignWorkerQuotaService $foreignWorkerQuotaService,
        private readonly MozambiqueLabourComplianceService $labourComplianceService,
        private readonly MozambiquePayrollObligationService $payrollObligationService
    ) {}

    public function snapshot(int $companyId): array
    {
        $today = Carbon::today();
        $next30Days = $today->copy()->addDays(30);

        $employees = Employee::query()->where('created_by', $companyId);
        $totalWorkers = (clone $employees)->count();

        $employeesWithoutNuit = (clone $employees)
            ->where(function ($query): void {
                $query->whereNull('tax_payer_id')->orWhere('tax_payer_id', '');
            })
            ->count();

        $employeesWithoutInss = (clone $employees)
            ->whereDoesntHave('socialSecurityProfile', function ($query): void {
                $query->whereNotNull('inss_number')->where('inss_number', '!=', '');
            })
            ->count();

        $employeeUserIds = (clone $employees)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $foreignProfiles = EmployeeForeignWorkerProfile::query()
            ->where('created_by', $companyId)
            ->where('is_foreign_worker', true);

        $foreignDocumentsExpired = (clone $foreignProfiles)
            ->where(function ($query) use ($today): void {
                $query
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('passport_expires_at')->whereDate('passport_expires_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('visa_expires_at')->whereDate('visa_expires_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('work_authorization_expires_at')->whereDate('work_authorization_expires_at', '<', $today->toDateString());
                    });
            })
            ->count();

        $foreignDocumentsExpiringSoon = (clone $foreignProfiles)
            ->where(function ($query) use ($today, $next30Days): void {
                $query
                    ->orWhere(function ($sub) use ($today, $next30Days): void {
                        $sub->whereNotNull('passport_expires_at')
                            ->whereDate('passport_expires_at', '>=', $today->toDateString())
                            ->whereDate('passport_expires_at', '<=', $next30Days->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today, $next30Days): void {
                        $sub->whereNotNull('visa_expires_at')
                            ->whereDate('visa_expires_at', '>=', $today->toDateString())
                            ->whereDate('visa_expires_at', '<=', $next30Days->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today, $next30Days): void {
                        $sub->whereNotNull('work_authorization_expires_at')
                            ->whereDate('work_authorization_expires_at', '>=', $today->toDateString())
                            ->whereDate('work_authorization_expires_at', '<=', $next30Days->toDateString());
                    });
            })
            ->count();

        $foreignCessationNotificationOverdue = (clone $foreignProfiles)
            ->where(function ($query) use ($today): void {
                $query
                    ->where(function ($sub) use ($today): void {
                        $sub->whereNotNull('cessation_notification_due_at')
                            ->whereNull('cessation_notified_at')
                            ->whereDate('cessation_notification_due_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNull('cessation_notification_due_at')
                            ->whereNotNull('cessation_effective_date')
                            ->whereNull('cessation_notified_at')
                            ->whereDate('cessation_effective_date', '<', $today->copy()->subDays(5)->toDateString());
                    });
            })
            ->count();

        $probationEndingSoon = EmployeeProbationProfile::query()
            ->where('created_by', $companyId)
            ->where('decision_status', 'ongoing')
            ->whereDate('expected_end_at', '>=', $today->toDateString())
            ->whereDate('expected_end_at', '<=', $next30Days->toDateString())
            ->count();

        $probationOverdue = EmployeeProbationProfile::query()
            ->where('created_by', $companyId)
            ->where('decision_status', 'ongoing')
            ->whereDate('expected_end_at', '<', $today->toDateString())
            ->count();

        $disciplinaryResponseOverdue = Warning::query()
            ->where('created_by', $companyId)
            ->where('status', 'pending')
            ->whereNotNull('response_deadline_at')
            ->whereDate('response_deadline_at', '<', $today->toDateString())
            ->count();

        $disciplinaryDecisionOverdue = Warning::query()
            ->where('created_by', $companyId)
            ->whereNotNull('decision_deadline_at')
            ->whereDate('decision_deadline_at', '<', $today->toDateString())
            ->where(function ($query): void {
                $query->whereNull('disciplinary_sanction')->orWhere('disciplinary_sanction', '');
            })
            ->count();

        $disciplinaryRefusalWithoutWitnesses = Warning::query()
            ->where('created_by', $companyId)
            ->where('worker_refused_note_of_culpa', true)
            ->where(function ($query): void {
                $query
                    ->whereNull('refusal_witness_one_name')
                    ->orWhere('refusal_witness_one_name', '')
                    ->orWhereNull('refusal_witness_two_name')
                    ->orWhere('refusal_witness_two_name', '');
            })
            ->count();

        $harassmentReportsPending = Complaint::query()
            ->where('created_by', $companyId)
            ->where('is_harassment_report', true)
            ->whereIn('status', ['pending', 'in review', 'assigned', 'in progress'])
            ->count();

        $harassmentReportsWithoutOwner = Complaint::query()
            ->where('created_by', $companyId)
            ->where('is_harassment_report', true)
            ->whereIn('status', ['in review', 'assigned', 'in progress'])
            ->whereNull('handling_owner_id')
            ->count();

        $offboardingChecklistPending = Termination::query()
            ->where('created_by', $companyId)
            ->where('status', 'approved')
            ->whereDate('termination_date', '<=', $today->toDateString())
            ->whereNull('offboarding_completed_at')
            ->count();

        $leaveMissingSupportingDocument = LeaveApplication::query()
            ->where('created_by', $companyId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereHas('leave_type', function ($query): void {
                $query->where('requires_supporting_document', true);
            })
            ->where(function ($query): void {
                $query->whereNull('attachment')->orWhere('attachment', '');
            })
            ->count();

        $legalLeaveMissingReferenceDate = LeaveApplication::query()
            ->where('created_by', $companyId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereHas('leave_type', function ($query): void {
                $query->whereIn('legal_code', ['maternity', 'paternity']);
            })
            ->whereNull('legal_reference_date')
            ->count();

        $leaveCashOutBelowMinimumRest = LeaveApplication::query()
            ->join('leave_types', 'leave_types.id', '=', 'leave_applications.leave_type_id')
            ->where('leave_applications.created_by', $companyId)
            ->where('leave_applications.compensated_days', '>', 0)
            ->whereNotNull('leave_types.min_effective_rest_days')
            ->whereRaw('COALESCE(leave_applications.effective_rest_days, leave_applications.total_days - leave_applications.compensated_days) < leave_types.min_effective_rest_days')
            ->count();

        $foreignWorkerUserIds = Employee::query()
            ->where('created_by', $companyId)
            ->whereHas('foreignWorkerProfile', function ($query): void {
                $query->where('is_foreign_worker', true);
            })
            ->pluck('user_id');

        $foreignOffboardingMigrationPending = Termination::query()
            ->where('created_by', $companyId)
            ->where('status', 'approved')
            ->whereDate('termination_date', '<=', $today->toDateString())
            ->whereNull('offboarding_migration_notified_at')
            ->whereIn('employee_id', $foreignWorkerUserIds)
            ->count();

        $labourPolicy = $this->labourComplianceService->getPolicy($companyId);
        $dailyOvertimeLimit = (float) ($labourPolicy['overtime_daily_limit_hours'] ?? 4.0);
        $weeklyOvertimeLimit = (float) ($labourPolicy['overtime_weekly_limit_hours'] ?? 16.0);

        $overtimeDailyLimitBreaches = Overtime::query()
            ->where('created_by', $companyId)
            ->where('total_days', '>', 0)
            ->get(['hours', 'total_days'])
            ->filter(function (Overtime $overtime) use ($dailyOvertimeLimit): bool {
                $days = max(1, (int) $overtime->total_days);
                $dailyHours = ((float) $overtime->hours) / $days;

                return $dailyHours > ($dailyOvertimeLimit + 0.0001);
            })
            ->count();

        $overtimeWeeklyLimitBreaches = $this->countWeeklyOvertimeBreaches($companyId, $weeklyOvertimeLimit);
        $weeklyRestBreachRisk = $this->countWeeklyRestBreachRisk($companyId, $today);

        $labourContracts = Contract::query()
            ->where('created_by', $companyId)
            ->where('source_type', 'contract')
            ->where('is_labour_contract', true);

        $labourContractsMissingType = (clone $labourContracts)
            ->where(function ($query): void {
                $query->whereNull('legal_contract_type')->orWhere('legal_contract_type', '');
            })
            ->count();

        $fixedTermWithoutJustification = (clone $labourContracts)
            ->whereIn('legal_contract_type', Contract::FIXED_TERM_TYPES)
            ->where(function ($query): void {
                $query->whereNull('fixed_term_justification')->orWhere('fixed_term_justification', '');
            })
            ->count();

        $labourContractsExpiringSoon = (clone $labourContracts)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $today->toDateString())
            ->whereDate('end_date', '<=', $next30Days->toDateString())
            ->count();

        $labourContractsExpired = (clone $labourContracts)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today->toDateString())
            ->count();

        $signedLabourContractUserIds = Contract::query()
            ->where('created_by', $companyId)
            ->where('source_type', 'contract')
            ->where('is_labour_contract', true)
            ->whereIn('user_id', $employeeUserIds->all())
            ->whereHas('signatures')
            ->pluck('user_id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $workersWithoutSignedContract = $employeeUserIds
            ->diff($signedLabourContractUserIds)
            ->count();

        $annualLeaveTypeIds = LeaveType::query()
            ->where('created_by', $companyId)
            ->where(function ($query): void {
                $query
                    ->where('legal_code', 'annual')
                    ->orWhereRaw('LOWER(name) like ?', ['%ferias%']);
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $annualLeaveCutoff = $today->copy()->subMonthsNoOverflow(12)->toDateString();
        $annualLeaveEligibleUsers = Employee::query()
            ->where('created_by', $companyId)
            ->whereNotNull('user_id')
            ->whereDate('date_of_joining', '<=', $annualLeaveCutoff)
            ->pluck('user_id')
            ->filter()
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($annualLeaveTypeIds->isEmpty()) {
            $accumulatedAnnualLeaveRisk = $annualLeaveEligibleUsers->count();
        } else {
            $usersWithAnnualLeaveInWindow = LeaveApplication::query()
                ->where('created_by', $companyId)
                ->where('status', 'approved')
                ->whereIn('employee_id', $annualLeaveEligibleUsers->all())
                ->whereIn('leave_type_id', $annualLeaveTypeIds->all())
                ->whereDate('start_date', '>=', $annualLeaveCutoff)
                ->pluck('employee_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->unique()
                ->values();

            $accumulatedAnnualLeaveRisk = $annualLeaveEligibleUsers
                ->diff($usersWithAnnualLeaveInWindow)
                ->count();
        }

        $hasActiveIrpsTable = MozIrpsTable::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today->toDateString())
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today->toDateString());
            })
            ->exists();

        $hasActiveInssRate = MozInssRate::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today->toDateString())
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today->toDateString());
            })
            ->exists();

        $hasActiveMinimumWage = MozMinimumWage::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $today->toDateString())
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today->toDateString());
            })
            ->exists();

        $quota = $this->foreignWorkerQuotaService->evaluate($companyId);
        $payrollObligations = $this->payrollObligationService->monthlySummary($companyId, 6);
        $disciplinaryCasesPending = Warning::query()
            ->where('created_by', $companyId)
            ->where('status', 'pending')
            ->count();

        $fiscalObligationsPending = (int) ($payrollObligations['totals']['pending_inss'] ?? 0)
            + (int) ($payrollObligations['totals']['overdue_inss'] ?? 0)
            + (int) ($payrollObligations['totals']['pending_irps'] ?? 0)
            + (int) ($payrollObligations['totals']['overdue_irps'] ?? 0);

        $foreignWorkersAtComplianceRiskBase = (clone $foreignProfiles)
            ->where(function ($query) use ($today, $next30Days): void {
                $query
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('passport_expires_at')->whereDate('passport_expires_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('visa_expires_at')->whereDate('visa_expires_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('work_authorization_expires_at')->whereDate('work_authorization_expires_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today, $next30Days): void {
                        $sub->whereNotNull('passport_expires_at')
                            ->whereDate('passport_expires_at', '>=', $today->toDateString())
                            ->whereDate('passport_expires_at', '<=', $next30Days->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today, $next30Days): void {
                        $sub->whereNotNull('visa_expires_at')
                            ->whereDate('visa_expires_at', '>=', $today->toDateString())
                            ->whereDate('visa_expires_at', '<=', $next30Days->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today, $next30Days): void {
                        $sub->whereNotNull('work_authorization_expires_at')
                            ->whereDate('work_authorization_expires_at', '>=', $today->toDateString())
                            ->whereDate('work_authorization_expires_at', '<=', $next30Days->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNotNull('cessation_notification_due_at')
                            ->whereNull('cessation_notified_at')
                            ->whereDate('cessation_notification_due_at', '<', $today->toDateString());
                    })
                    ->orWhere(function ($sub) use ($today): void {
                        $sub->whereNull('cessation_notification_due_at')
                            ->whereNotNull('cessation_effective_date')
                            ->whereNull('cessation_notified_at')
                            ->whereDate('cessation_effective_date', '<', $today->copy()->subDays(5)->toDateString());
                    });
            })
            ->count();

        $foreignQuotaOverflowWorkers = max(
            0,
            (int) ($quota['current_foreign_workers'] ?? 0) - (int) ($quota['quota_slots'] ?? 0)
        );
        $foreignWorkersAtComplianceRisk = max($foreignWorkersAtComplianceRiskBase, $foreignQuotaOverflowWorkers);

        $items = [
            $this->item('employees_without_nuit', __('Workers without NUIT'), $employeesWithoutNuit, 'high'),
            $this->item('employees_without_inss', __('Workers without INSS'), $employeesWithoutInss, 'high'),
            $this->item('foreign_documents_expired', __('Foreign documents expired'), $foreignDocumentsExpired, 'high'),
            $this->item('foreign_documents_expiring_30d', __('Foreign documents expiring in 30 days'), $foreignDocumentsExpiringSoon, 'medium'),
            $this->item('foreign_cessation_notification_overdue', __('Foreign cessation notifications overdue'), $foreignCessationNotificationOverdue, 'high'),
            $this->item('probation_overdue', __('Probation overdue without decision'), $probationOverdue, 'high'),
            $this->item('probation_ending_30d', __('Probation ending in 30 days'), $probationEndingSoon, 'medium'),
            $this->item('disciplinary_response_overdue', __('Disciplinary response deadlines overdue'), $disciplinaryResponseOverdue, 'high'),
            $this->item('disciplinary_decision_overdue', __('Disciplinary decisions overdue'), $disciplinaryDecisionOverdue, 'high'),
            $this->item('disciplinary_refusal_without_witnesses', __('Disciplinary refusals without two witnesses'), $disciplinaryRefusalWithoutWitnesses, 'high'),
            $this->item('harassment_reports_pending', __('Harassment reports pending resolution'), $harassmentReportsPending, 'high'),
            $this->item('harassment_reports_without_owner', __('Harassment reports without assigned owner'), $harassmentReportsWithoutOwner, 'high'),
            $this->item('offboarding_checklist_pending', __('Approved terminations without offboarding completion'), $offboardingChecklistPending, 'medium'),
            $this->item('foreign_offboarding_migration_pending', __('Foreign worker terminations missing migration notification'), $foreignOffboardingMigrationPending, 'high'),
            $this->item('leave_missing_supporting_document', __('Leave records missing required supporting documents'), $leaveMissingSupportingDocument, 'high'),
            $this->item('legal_leave_missing_reference_date', __('Legal leave records missing reference date'), $legalLeaveMissingReferenceDate, 'medium'),
            $this->item('leave_cash_out_below_min_rest', __('Leave compensation below minimum effective rest days'), $leaveCashOutBelowMinimumRest, 'high'),
            $this->item('overtime_daily_limit_breaches', __('Overtime daily legal limit breaches'), $overtimeDailyLimitBreaches, 'high'),
            $this->item('overtime_weekly_limit_breaches', __('Overtime weekly legal limit breaches'), $overtimeWeeklyLimitBreaches, 'high'),
            $this->item('weekly_rest_breach_risk', __('Workers at risk of missing 24h weekly rest'), $weeklyRestBreachRisk, 'high'),
            $this->item('workers_without_signed_contract', __('Workers without signed labour contract'), $workersWithoutSignedContract, 'high'),
            $this->item('labour_contracts_missing_type', __('Labour contracts missing legal type'), $labourContractsMissingType, 'high'),
            $this->item('fixed_term_without_justification', __('Fixed-term contracts without legal justification'), $fixedTermWithoutJustification, 'high'),
            $this->item('labour_contracts_expiring_30d', __('Labour contracts expiring in 30 days'), $labourContractsExpiringSoon, 'medium'),
            $this->item('labour_contracts_expired', __('Labour contracts already expired'), $labourContractsExpired, 'high'),
            $this->item('accumulated_annual_leave_risk', __('Workers with potential accumulated annual leave'), $accumulatedAnnualLeaveRisk, 'medium'),
            $this->item('disciplinary_cases_pending', __('Disciplinary cases pending closure'), $disciplinaryCasesPending, 'medium'),
            $this->item('missing_active_irps_table', __('Active IRPS table missing'), $hasActiveIrpsTable ? 0 : 1, 'high'),
            $this->item('missing_active_inss_rate', __('Active INSS rate missing'), $hasActiveInssRate ? 0 : 1, 'high'),
            $this->item('missing_active_minimum_wage', __('Active minimum wage table missing'), $hasActiveMinimumWage ? 0 : 1, 'medium'),
            $this->item('foreign_quota_exceeded', __('Foreign worker quota exceeded'), $quota['is_exceeded'] ? 1 : 0, 'high'),
            $this->item('foreign_workers_at_compliance_risk', __('Foreign workers at compliance risk'), $foreignWorkersAtComplianceRisk, 'high'),
            $this->item('payroll_fiscal_obligations_pending', __('Fiscal obligations pending (INSS/IRPS)'), $fiscalObligationsPending, 'high'),
            $this->item(
                'payroll_inss_submission_overdue',
                __('INSS monthly submissions overdue'),
                (int) ($payrollObligations['totals']['overdue_inss'] ?? 0),
                'high'
            ),
            $this->item(
                'payroll_irps_submission_overdue',
                __('IRPS monthly submissions overdue'),
                (int) ($payrollObligations['totals']['overdue_irps'] ?? 0),
                'high'
            ),
        ];

        $triggeredItems = collect($items)->filter(fn ($item) => $item['count'] > 0)->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'metrics' => [
                'total_workers' => $totalWorkers,
                'triggered_alerts' => $triggeredItems->count(),
                'high_alerts' => $triggeredItems->where('severity', 'high')->count(),
                'medium_alerts' => $triggeredItems->where('severity', 'medium')->count(),
            ],
            'items' => $items,
            'compliance_panel' => $this->buildCompliancePanel([
                $this->panelIndicator('workers_without_signed_contract', __('Workers without signed labour contract'), $workersWithoutSignedContract, 'high'),
                $this->panelIndicator('workers_without_nuit', __('Workers without NUIT'), $employeesWithoutNuit, 'high'),
                $this->panelIndicator('workers_without_inss', __('Workers without INSS'), $employeesWithoutInss, 'high'),
                $this->panelIndicator('labour_contracts_expired', __('Labour contracts already expired'), $labourContractsExpired, 'high'),
                $this->panelIndicator('foreign_documents_expired', __('Foreign documents expired'), $foreignDocumentsExpired, 'high'),
                $this->panelIndicator('accumulated_annual_leave_risk', __('Workers with potential accumulated annual leave'), $accumulatedAnnualLeaveRisk, 'medium'),
                $this->panelIndicator('disciplinary_cases_pending', __('Disciplinary cases pending closure'), $disciplinaryCasesPending, 'medium'),
                $this->panelIndicator('payroll_fiscal_obligations_pending', __('Fiscal obligations pending (INSS/IRPS)'), $fiscalObligationsPending, 'high'),
                $this->panelIndicator('foreign_workers_at_compliance_risk', __('Foreign workers at compliance risk'), $foreignWorkersAtComplianceRisk, 'high'),
            ]),
            'quota' => $quota,
            'payroll_obligations' => $payrollObligations,
        ];
    }

    private function item(string $key, string $label, int $count, string $severity): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'severity' => $severity,
        ];
    }

    private function panelIndicator(string $key, string $label, int $count, string $severity): array
    {
        $status = $count > 0
            ? ($severity === 'high' ? 'high_risk' : 'attention')
            : 'ok';

        return [
            'key' => $key,
            'label' => $label,
            'count' => max(0, $count),
            'severity' => $severity,
            'status' => $status,
        ];
    }

    private function buildCompliancePanel(array $indicators): array
    {
        $collection = collect($indicators)->values();

        return [
            'generated_at' => now()->toIso8601String(),
            'indicators' => $collection->all(),
            'metrics' => [
                'total_indicators' => $collection->count(),
                'triggered_indicators' => $collection->where('count', '>', 0)->count(),
                'high_risk_indicators' => $collection->where('status', 'high_risk')->count(),
                'attention_indicators' => $collection->where('status', 'attention')->count(),
                'ok_indicators' => $collection->where('status', 'ok')->count(),
            ],
        ];
    }

    private function countWeeklyOvertimeBreaches(int $companyId, float $weeklyLimit): int
    {
        if ($weeklyLimit <= 0) {
            return 0;
        }

        $weeklyTotals = [];

        $overtimes = Overtime::query()
            ->where('created_by', $companyId)
            ->get(['employee_id', 'hours', 'total_days', 'start_date', 'end_date']);

        foreach ($overtimes as $overtime) {
            $start = Carbon::parse($overtime->start_date)->startOfDay();
            $end = Carbon::parse($overtime->end_date)->startOfDay();
            $totalDays = max(1, $start->diffInDays($end) + 1);
            $dailyHours = ((float) $overtime->hours) / $totalDays;

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $weekKey = $cursor->format('o-W');
                $employeeWeekKey = $overtime->employee_id . '|' . $weekKey;
                $weeklyTotals[$employeeWeekKey] = ($weeklyTotals[$employeeWeekKey] ?? 0.0) + $dailyHours;
            }
        }

        return collect($weeklyTotals)
            ->filter(fn (float $hours): bool => $hours > ($weeklyLimit + 0.0001))
            ->count();
    }

    private function countWeeklyRestBreachRisk(int $companyId, Carbon $today): int
    {
        $windowStart = $today->copy()->subDays(13)->toDateString();
        $windowEnd = $today->toDateString();

        $attendances = Attendance::query()
            ->where('created_by', $companyId)
            ->whereIn('status', ['present', 'half day'])
            ->whereDate('date', '>=', $windowStart)
            ->whereDate('date', '<=', $windowEnd)
            ->get(['employee_id', 'date'])
            ->groupBy('employee_id');

        $workersAtRisk = 0;

        foreach ($attendances as $employeeAttendances) {
            $attendanceDates = $employeeAttendances
                ->map(fn (Attendance $attendance): string => Carbon::parse($attendance->date)->toDateString())
                ->unique()
                ->values()
                ->flip();

            $hasSevenConsecutiveDays = false;
            $segmentStart = $today->copy()->subDays(13);

            while ($segmentStart->lte($today->copy()->subDays(6))) {
                $segmentEnd = $segmentStart->copy()->addDays(6);
                $allDaysWorked = true;

                for ($cursor = $segmentStart->copy(); $cursor->lte($segmentEnd); $cursor->addDay()) {
                    if (!$attendanceDates->has($cursor->toDateString())) {
                        $allDaysWorked = false;
                        break;
                    }
                }

                if ($allDaysWorked) {
                    $hasSevenConsecutiveDays = true;
                    break;
                }

                $segmentStart->addDay();
            }

            if ($hasSevenConsecutiveDays) {
                $workersAtRisk++;
            }
        }

        return $workersAtRisk;
    }
}
