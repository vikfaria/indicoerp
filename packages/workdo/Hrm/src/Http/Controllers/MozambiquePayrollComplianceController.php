<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Models\CostCenter;
use App\Services\MozambiqueForeignWorkerComplianceReportService;
use App\Services\MozambiqueHrComplianceAlertService;
use App\Services\MozambiquePayrollAccountingExportService;
use App\Services\MozambiqueHrComplianceDashboardService;
use App\Services\MozambiqueHrLegalSettingsService;
use App\Services\MozambiqueHrWorkforceExportService;
use App\Services\MozambiqueLabourComplianceService;
use App\Services\MozambiquePayrollSubmissionReportService;
use App\Services\PayrollCostCenterAllocatorService;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Hrm\Models\Employee;

class MozambiquePayrollComplianceController extends Controller
{
    public function __construct(
        private readonly MozambiqueLabourComplianceService $labourComplianceService,
        private readonly MozambiqueHrComplianceDashboardService $complianceDashboardService,
        private readonly MozambiqueHrComplianceAlertService $complianceAlertService,
        private readonly MozambiquePayrollSubmissionReportService $payrollSubmissionReportService,
        private readonly MozambiqueForeignWorkerComplianceReportService $foreignWorkerComplianceReportService,
        private readonly MozambiqueHrLegalSettingsService $legalSettingsService,
        private readonly MozambiquePayrollAccountingExportService $payrollAccountingExportService,
        private readonly MozambiqueHrWorkforceExportService $workforceExportService,
        private readonly PayrollCostCenterAllocatorService $payrollCostCenterAllocatorService
    ) {}

    public function index()
    {
        if (!Auth::user()->can('manage-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $companyId = creatorId();

        $irpsTables = MozIrpsTable::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->with(['brackets' => function ($query): void {
                $query->orderBy('sequence')->orderBy('range_from');
            }])
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        $inssRates = MozInssRate::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        $minimumWages = MozMinimumWage::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->orderBy('sector_code')
            ->orderByDesc('effective_from')
            ->get();

        $costCenterOptions = CostCenter::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $departmentOptions = Department::query()
            ->where('created_by', $companyId)
            ->orderBy('department_name')
            ->get(['id', 'department_name']);

        $branchOptions = Branch::query()
            ->where('created_by', $companyId)
            ->orderBy('branch_name')
            ->get(['id', 'branch_name']);

        $employeeOptions = Employee::query()
            ->where('created_by', $companyId)
            ->with('user:id,name')
            ->orderBy('id')
            ->get(['id', 'user_id'])
            ->map(static fn (Employee $employee): array => [
                'id' => $employee->id,
                'name' => $employee->user?->name ?? ('Employee #' . $employee->id),
            ])
            ->values();

        $complianceSnapshot = $this->complianceDashboardService->snapshot($companyId);
        $complianceAlerts = $this->complianceAlertService->syncFromSnapshot($companyId, $complianceSnapshot);

        return Inertia::render('Hrm/SystemSetup/MozambiquePayroll/Index', [
            'irpsTables' => $irpsTables,
            'inssRates' => $inssRates,
            'minimumWages' => $minimumWages,
            'labourPolicy' => $this->labourComplianceService->getPolicy($companyId),
            'complianceSnapshot' => $complianceSnapshot,
            'complianceAlerts' => $complianceAlerts,
            'legalSettings' => $this->legalSettingsService->getSettings($companyId),
            'costCenters' => $costCenterOptions,
            'departments' => $departmentOptions,
            'branches' => $branchOptions,
            'employees' => $employeeOptions,
            'costCenterMappingConfig' => $this->payrollCostCenterAllocatorService->getConfiguration($companyId),
        ]);
    }

    public function updateCostCenterMappings(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'mode' => 'required|string|in:configured,configured_with_heuristic',
            'mappings' => 'required|array',
            'mappings.employee' => 'nullable|array',
            'mappings.department' => 'nullable|array',
            'mappings.branch' => 'nullable|array',
            'mappings.employee.*' => 'nullable|integer',
            'mappings.department.*' => 'nullable|integer',
            'mappings.branch.*' => 'nullable|integer',
        ]);

        $allowedCostCenterIds = CostCenter::query()
            ->where('company_id', creatorId())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $allowedLookup = array_fill_keys($allowedCostCenterIds, true);
        $mappings = $validated['mappings'] ?? [];

        foreach (['employee', 'department', 'branch'] as $type) {
            $items = $mappings[$type] ?? [];
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $sourceId => $costCenterId) {
                if ((int) $costCenterId <= 0) {
                    continue;
                }

                if (!isset($allowedLookup[(int) $costCenterId])) {
                    return back()->with('error', __('Invalid cost center selected for mapping.'));
                }
            }
        }

        $this->payrollCostCenterAllocatorService->saveConfiguration(creatorId(), [
            'mode' => $validated['mode'],
            'mappings' => $mappings,
        ]);

        return back()->with('success', __('Payroll cost center mappings updated successfully.'));
    }

    public function updateLabourPolicy(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'overtime_daily_limit_hours' => 'nullable|numeric|min:0.25|max:24',
            'overtime_weekly_limit_hours' => 'nullable|numeric|min:1|max:168',
            'overtime_monthly_limit_hours' => 'nullable|numeric|min:1|max:744',
            'overtime_quarterly_limit_hours' => 'nullable|numeric|min:1|max:2208',
            'overtime_yearly_limit_hours' => 'nullable|numeric|min:1|max:9999',
            'leave_min_notice_days' => 'required|integer|min:0|max:365',
            'leave_max_consecutive_days' => 'nullable|integer|min:1|max:366',
            'leave_count_non_working_days' => 'required|boolean',
            'leave_count_holidays' => 'required|boolean',
        ]);

        setSetting('mz_overtime_daily_limit_hours', $validated['overtime_daily_limit_hours'] ?? '');
        setSetting('mz_overtime_weekly_limit_hours', $validated['overtime_weekly_limit_hours'] ?? '');
        setSetting('mz_overtime_monthly_limit_hours', $validated['overtime_monthly_limit_hours'] ?? '');
        setSetting('mz_overtime_quarterly_limit_hours', $validated['overtime_quarterly_limit_hours'] ?? '');
        setSetting('mz_overtime_yearly_limit_hours', $validated['overtime_yearly_limit_hours'] ?? '');
        setSetting('mz_leave_min_notice_days', (string) $validated['leave_min_notice_days']);
        setSetting('mz_leave_max_consecutive_days', $validated['leave_max_consecutive_days'] ?? '');
        setSetting('mz_leave_count_non_working_days', $validated['leave_count_non_working_days'] ? '1' : '0');
        setSetting('mz_leave_count_holidays', $validated['leave_count_holidays'] ? '1' : '0');

        return back()->with('success', __('Mozambique labour policy updated successfully.'));
    }

    public function updateLegalSettings(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'foreign_quota' => 'required|array',
            'foreign_quota.micro_max_workers' => 'required|integer|min:1|max:500000',
            'foreign_quota.small_max_workers' => 'required|integer|min:2|max:500000',
            'foreign_quota.medium_max_workers' => 'required|integer|min:3|max:500000',
            'foreign_quota.micro_quota_percent' => 'required|numeric|min:0|max:100',
            'foreign_quota.small_quota_percent' => 'required|numeric|min:0|max:100',
            'foreign_quota.medium_quota_percent' => 'required|numeric|min:0|max:100',
            'foreign_quota.large_quota_percent' => 'required|numeric|min:0|max:100',
            'probation_limits_days' => 'required|array',
            'probation_limits_days.base_indefinite' => 'required|integer|min:1|max:365',
            'probation_limits_days.general' => 'required|integer|min:1|max:365',
            'probation_limits_days.technician_mid' => 'required|integer|min:1|max:365',
            'probation_limits_days.technician_high' => 'required|integer|min:1|max:365',
            'probation_limits_days.leadership' => 'required|integer|min:1|max:365',
            'probation_alert_days' => 'required|array',
            'probation_alert_days.primary' => 'required|integer|min:1|max:365',
            'probation_alert_days.secondary' => 'required|integer|min:0|max:365',
        ]);

        $quota = $validated['foreign_quota'];
        if ((int) $quota['small_max_workers'] <= (int) $quota['micro_max_workers']) {
            return back()->withErrors([
                'foreign_quota.small_max_workers' => __('Small employer max workers must be greater than micro employer max workers.'),
            ]);
        }

        if ((int) $quota['medium_max_workers'] <= (int) $quota['small_max_workers']) {
            return back()->withErrors([
                'foreign_quota.medium_max_workers' => __('Medium employer max workers must be greater than small employer max workers.'),
            ]);
        }

        if ((int) $validated['probation_alert_days']['secondary'] > (int) $validated['probation_alert_days']['primary']) {
            return back()->withErrors([
                'probation_alert_days.secondary' => __('Secondary probation alert day must be less than or equal to primary alert day.'),
            ]);
        }

        $this->legalSettingsService->updateSettings(creatorId(), $validated);

        return back()->with('success', __('Mozambique legal settings updated successfully.'));
    }

    public function storeIrpsTable(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        MozIrpsTable::create([
            'name' => $validated['name'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => creatorId(),
        ]);

        return back()->with('success', __('IRPS table created successfully.'));
    }

    public function updateIrpsTable(Request $request, MozIrpsTable $table)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $table->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        $table->update([
            'name' => $validated['name'],
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return back()->with('success', __('IRPS table updated successfully.'));
    }

    public function destroyIrpsTable(MozIrpsTable $table)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $table->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $table->delete();

        return back()->with('success', __('IRPS table deleted successfully.'));
    }

    public function storeIrpsBracket(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'irps_table_id' => 'required|integer|exists:mz_irps_tables,id',
            'range_from' => 'required|numeric|min:0',
            'range_to' => 'nullable|numeric|gt:range_from',
            'fixed_amount' => 'required|numeric|min:0',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'sequence' => 'required|integer|min:1',
        ]);

        $table = MozIrpsTable::query()->findOrFail($validated['irps_table_id']);
        if ((int) $table->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        MozIrpsBracket::create($validated);

        return back()->with('success', __('IRPS bracket created successfully.'));
    }

    public function updateIrpsBracket(Request $request, MozIrpsBracket $bracket)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $bracket->irpsTable?->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'range_from' => 'required|numeric|min:0',
            'range_to' => 'nullable|numeric|gt:range_from',
            'fixed_amount' => 'required|numeric|min:0',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'sequence' => 'required|integer|min:1',
        ]);

        $bracket->update($validated);

        return back()->with('success', __('IRPS bracket updated successfully.'));
    }

    public function destroyIrpsBracket(MozIrpsBracket $bracket)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $bracket->irpsTable?->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $bracket->delete();

        return back()->with('success', __('IRPS bracket deleted successfully.'));
    }

    public function storeInssRate(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'employee_rate' => 'required|numeric|min:0|max:100',
            'employer_rate' => 'required|numeric|min:0|max:100',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        MozInssRate::create([
            ...$validated,
            'created_by' => creatorId(),
        ]);

        return back()->with('success', __('INSS rate created successfully.'));
    }

    public function updateInssRate(Request $request, MozInssRate $rate)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $rate->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'employee_rate' => 'required|numeric|min:0|max:100',
            'employer_rate' => 'required|numeric|min:0|max:100',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        $rate->update($validated);

        return back()->with('success', __('INSS rate updated successfully.'));
    }

    public function destroyInssRate(MozInssRate $rate)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $rate->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $rate->delete();

        return back()->with('success', __('INSS rate deleted successfully.'));
    }

    public function storeMinimumWage(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'sector_code' => 'required|string|max:50',
            'sector_name' => 'required|string|max:120',
            'monthly_amount' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        MozMinimumWage::create([
            ...$validated,
            'sector_code' => strtoupper(trim($validated['sector_code'])),
            'created_by' => creatorId(),
        ]);

        return back()->with('success', __('Minimum wage row created successfully.'));
    }

    public function updateMinimumWage(Request $request, MozMinimumWage $wage)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $wage->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'sector_code' => 'required|string|max:50',
            'sector_name' => 'required|string|max:120',
            'monthly_amount' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);

        $wage->update([
            ...$validated,
            'sector_code' => strtoupper(trim($validated['sector_code'])),
        ]);

        return back()->with('success', __('Minimum wage row updated successfully.'));
    }

    public function destroyMinimumWage(MozMinimumWage $wage)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $wage->created_by !== (int) creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $wage->delete();

        return back()->with('success', __('Minimum wage row deleted successfully.'));
    }

    public function exportModelo19Support(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollSubmissionReportService->buildModelo19Dataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'modelo19-irps-support-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Submission Due Date',
            'Payroll ID',
            'Payroll Title',
            'Pay Date',
            'Employee',
            'Employee NUIT',
            'Residency Status',
            'Eligible Dependents',
            'Gross Pay',
            'Taxable Income',
            'Dependent Deduction',
            'Adjusted Taxable Income',
            'IRPS Rule',
            'IRPS Rate %',
            'IRPS Withheld',
            'Net Pay',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row) use ($dataset): array {
            return [
                $row['reference_period'],
                $dataset['submission_due_date'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_date'],
                $row['employee_name'],
                $row['employee_nuit'],
                $row['residency_status'],
                $row['eligible_dependents_count'],
                $row['gross_pay'],
                $row['taxable_income'],
                $row['dependent_deduction_total'],
                $row['adjusted_taxable_income'],
                $row['irps_rule'],
                $row['irps_rate_percent'],
                $row['irps_amount'],
                $row['net_pay'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportInssGuide(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollSubmissionReportService->buildInssDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'inss-monthly-guide-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Submission Due Date',
            'Payroll ID',
            'Payroll Title',
            'Pay Date',
            'Employee',
            'Employee NUIT',
            'Contributive Base',
            'Employee Rate %',
            'Employee Contribution',
            'Employer Rate %',
            'Employer Contribution',
            'Total INSS Contribution',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row) use ($dataset): array {
            return [
                $row['reference_period'],
                $dataset['submission_due_date'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_date'],
                $row['employee_name'],
                $row['employee_nuit'],
                $row['contributive_base'],
                $row['employee_rate_percent'],
                $row['employee_contribution'],
                $row['employer_rate_percent'],
                $row['employer_contribution'],
                $row['total_contribution'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportBankPaymentFile(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollSubmissionReportService->buildBankPaymentDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-bank-payment-file-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Payment Reference',
            'Payroll ID',
            'Payroll Title',
            'Pay Date',
            'Employee',
            'Employee NUIT',
            'Account Holder Name',
            'Bank Name',
            'Bank Branch',
            'Bank Identifier Code',
            'Account Number',
            'Currency',
            'Net Pay',
            'Payroll Entry Status',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'],
                $row['payment_reference'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_date'],
                $row['employee_name'],
                $row['employee_nuit'],
                $row['account_holder_name'],
                $row['bank_name'],
                $row['bank_branch'],
                $row['bank_identifier_code'],
                $row['account_number'],
                $row['currency'],
                $row['net_pay'],
                $row['payroll_entry_status'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportExpatriatesReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
            'window_days' => 'nullable|integer|min:1|max:180',
        ]);

        $dataset = $this->foreignWorkerComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null,
            (int) ($validated['window_days'] ?? 30)
        );

        $filename = sprintf(
            'expatriates-compliance-%s-%s.csv',
            $dataset['report_date'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Report Date',
            'Window Days',
            'Employer Type',
            'Quota Slots',
            'Quota Used',
            'Quota Available',
            'Quota Exceeded',
            'Foreign Workers Total',
            'Work Authorizations Expiring',
            'Visas Expiring',
            'Contracts Expiring',
            'Migration Notifications Pending',
            'Migration Notifications Overdue',
            'Employee',
            'Employee Internal ID',
            'Employee NUIT',
            'Nationality',
            'Residency Status',
            'Hiring Regime',
            'Work Province',
            'Passport Number',
            'Passport Expires At',
            'Passport Status',
            'Visa Type',
            'Visa Expires At',
            'Visa Status',
            'Work Authorization Number',
            'Work Authorization Expires At',
            'Work Authorization Status',
            'Contract Number',
            'Contract End Date',
            'Contract Status',
            'Contract Expiring In Window',
            'Cessation Effective Date',
            'Cessation Notification Due Date',
            'Cessation Notified At',
            'Migration Notification Status',
        ];

        $summary = $dataset['summary'] ?? [];
        $quota = $dataset['quota'] ?? [];
        $detailRows = collect($dataset['rows'] ?? [])->values();

        if ($detailRows->isEmpty()) {
            $detailRows = collect([[
                'employee_name' => '',
                'employee_internal_id' => '',
                'employee_nuit' => '',
                'nationality' => '',
                'residency_status' => '',
                'hiring_regime' => '',
                'work_province' => '',
                'passport_number' => '',
                'passport_expires_at' => '',
                'passport_status' => '',
                'visa_type' => '',
                'visa_expires_at' => '',
                'visa_status' => '',
                'work_authorization_number' => '',
                'work_authorization_expires_at' => '',
                'work_authorization_status' => '',
                'contract_number' => '',
                'contract_end_date' => '',
                'contract_status' => '',
                'contract_expiring_in_window' => false,
                'cessation_effective_date' => '',
                'cessation_notification_due_at' => '',
                'cessation_notified_at' => '',
                'migration_notification_status' => '',
            ]]);
        }

        $rows = $detailRows->map(function (array $row) use ($dataset, $summary, $quota): array {
            return [
                $dataset['report_date'],
                $dataset['window_days'],
                $quota['employer_type'] ?? '',
                $quota['quota_slots'] ?? 0,
                $quota['current_foreign_workers'] ?? 0,
                $quota['remaining_slots'] ?? 0,
                ($quota['is_exceeded'] ?? false) ? 'yes' : 'no',
                $summary['total_foreign_workers'] ?? 0,
                $summary['work_authorizations_expiring'] ?? 0,
                $summary['visas_expiring'] ?? 0,
                $summary['contracts_expiring'] ?? 0,
                $summary['migration_notifications_pending'] ?? 0,
                $summary['migration_notifications_overdue'] ?? 0,
                $row['employee_name'] ?? '',
                $row['employee_internal_id'] ?? '',
                $row['employee_nuit'] ?? '',
                $row['nationality'] ?? '',
                $row['residency_status'] ?? '',
                $row['hiring_regime'] ?? '',
                $row['work_province'] ?? '',
                $row['passport_number'] ?? '',
                $row['passport_expires_at'] ?? '',
                $row['passport_status'] ?? '',
                $row['visa_type'] ?? '',
                $row['visa_expires_at'] ?? '',
                $row['visa_status'] ?? '',
                $row['work_authorization_number'] ?? '',
                $row['work_authorization_expires_at'] ?? '',
                $row['work_authorization_status'] ?? '',
                $row['contract_number'] ?? '',
                $row['contract_end_date'] ?? '',
                $row['contract_status'] ?? '',
                ($row['contract_expiring_in_window'] ?? false) ? 'yes' : 'no',
                $row['cessation_effective_date'] ?? '',
                $row['cessation_notification_due_at'] ?? '',
                $row['cessation_notified_at'] ?? '',
                $row['migration_notification_status'] ?? '',
            ];
        })->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportCostAllocationReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildCostAllocationDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-cost-allocation-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Payroll ID',
            'Payroll Title',
            'Pay Date',
            'Employee',
            'Employee NUIT',
            'Branch',
            'Department',
            'Designation',
            'Cost Center Code',
            'Cost Center Name',
            'Gross Pay',
            'Allowances',
            'Manual Overtime',
            'Deductions',
            'Loans',
            'IRPS',
            'INSS Employee',
            'INSS Employer',
            'Net Pay',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_date'],
                $row['employee_name'],
                $row['employee_nuit'],
                $row['branch'],
                $row['department'],
                $row['designation'],
                $row['cost_center_code'],
                $row['cost_center_name'],
                $row['gross_pay'],
                $row['total_allowances'],
                $row['total_manual_overtimes'],
                $row['total_deductions'],
                $row['total_loans'],
                $row['irps_amount'],
                $row['inss_employee_amount'],
                $row['inss_employer_amount'],
                $row['net_pay'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportAccountingJournalLines(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildJournalLinesDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-journal-lines-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Journal ID',
            'Journal Number',
            'Journal Date',
            'Journal Code',
            'Journal Name',
            'Line Number',
            'Payroll Entry ID',
            'Payroll ID',
            'Payroll Title',
            'Employee',
            'Account Code',
            'Account Name',
            'Cost Center Code',
            'Cost Center Name',
            'Debit',
            'Credit',
            'Description',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'],
                $row['journal_id'],
                $row['journal_number'],
                $row['journal_date'],
                $row['journal_code'],
                $row['journal_name'],
                $row['line_number'],
                $row['payroll_entry_id'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['employee_name'],
                $row['account_code'],
                $row['account_name'],
                $row['cost_center_code'],
                $row['cost_center_name'],
                $row['debit_amount'],
                $row['credit_amount'],
                $row['description'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportCostAllocationJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildCostAllocationDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportAccountingJournalLinesJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildJournalLinesDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportCostAllocationXml(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildCostAllocationDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-cost-allocation-%s-%s.xml',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXml('payroll_cost_allocation', $dataset, $filename);
    }

    public function exportWorkforceRegister(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
        ]);

        $dataset = $this->workforceExportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null
        );

        $filename = sprintf(
            'hr-workforce-register-%s-%s.csv',
            $dataset['reference_date'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Date',
            'Employee Record ID',
            'Employee Internal ID',
            'Employee',
            'Employment Type',
            'Date of Joining',
            'Branch',
            'Department',
            'Designation',
            'Employee NUIT',
            'Basic Salary',
            'INSS Number',
            'INSS Status',
            'INSS Registration Date',
            'Dependents Total',
            'Dependents Tax Eligible',
            'Is Foreign Worker',
            'Nationality',
            'Residency Status',
            'Hiring Regime',
            'Work Province',
            'Passport Number',
            'Passport Expires At',
            'Visa Type',
            'Visa Expires At',
            'Work Authorization Number',
            'Work Authorization Expires At',
            'Mozambique Entry Date',
            'Cessation Effective Date',
            'Cessation Notification Due Date',
            'Cessation Notified At',
            'Probation Category',
            'Probation Starts At',
            'Probation Expected End',
            'Probation Evaluation',
            'Probation Decision',
            'Probation Decision Date',
            'Contract Number',
            'Contract Status',
            'Contract Start Date',
            'Contract End Date',
            'Contract Legal Type',
            'Contract Fixed-term Justification',
            'Contract Presumed Indefinite Risk',
            'Approved Leave Days (Year)',
            'Approved Leave Days (Total)',
            'Latest Pay Date',
            'Latest Gross Pay',
            'Latest Net Pay',
            'Latest IRPS',
            'Latest INSS Employee',
            'Latest INSS Employer',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_date'],
                $row['employee_record_id'],
                $row['employee_internal_id'],
                $row['employee_name'],
                $row['employment_type'],
                $row['date_of_joining'],
                $row['branch'],
                $row['department'],
                $row['designation'],
                $row['employee_nuit'],
                $row['basic_salary'],
                $row['inss_number'],
                $row['inss_registration_status'],
                $row['inss_registration_date'],
                $row['dependents_total'],
                $row['dependents_tax_eligible'],
                $row['is_foreign_worker'] ? 'yes' : 'no',
                $row['nationality'],
                $row['residency_status'],
                $row['hiring_regime'],
                $row['work_province'],
                $row['passport_number'],
                $row['passport_expires_at'],
                $row['visa_type'],
                $row['visa_expires_at'],
                $row['work_authorization_number'],
                $row['work_authorization_expires_at'],
                $row['mozambique_entry_date'],
                $row['cessation_effective_date'],
                $row['cessation_notification_due_at'],
                $row['cessation_notified_at'],
                $row['probation_category'],
                $row['probation_starts_at'],
                $row['probation_expected_end_at'],
                $row['probation_evaluation_status'],
                $row['probation_decision_status'],
                $row['probation_decision_date'],
                $row['contract_number'],
                $row['contract_status'],
                $row['contract_start_date'],
                $row['contract_end_date'],
                $row['contract_legal_type'],
                $row['contract_fixed_term_justification'],
                $row['contract_presumed_indefinite_risk'] ? 'yes' : 'no',
                $row['approved_leave_days_current_year'],
                $row['approved_leave_days_total'],
                $row['latest_pay_date'],
                $row['latest_gross_pay'],
                $row['latest_net_pay'],
                $row['latest_irps_amount'],
                $row['latest_inss_employee_amount'],
                $row['latest_inss_employer_amount'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function importWorkforceRegister(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $summary = $this->workforceExportService->importCsv(
            creatorId(),
            $validated['csv_file']->getRealPath() ?: '',
            Auth::id()
        );

        if (($summary['processed'] ?? 0) === 0 && !empty($summary['errors'])) {
            return back()->with('error', $summary['errors'][0]['message'] ?? __('Workforce import failed.'));
        }

        $errorCount = count($summary['errors'] ?? []);
        $message = __('Workforce import completed. Processed: :processed, Updated: :updated, Skipped: :skipped.', [
            'processed' => (int) ($summary['processed'] ?? 0),
            'updated' => (int) ($summary['updated'] ?? 0),
            'skipped' => (int) ($summary['skipped'] ?? 0),
        ]);

        if ($errorCount > 0) {
            $message .= ' ' . __('Rows with issues: :count.', ['count' => $errorCount]);
        }

        return back()->with('success', $message);
    }

    public function exportWorkforceRegisterJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
        ]);

        $dataset = $this->workforceExportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportWorkforceRegisterXml(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
        ]);

        $dataset = $this->workforceExportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null
        );

        $filename = sprintf(
            'hr-workforce-register-%s-%s.xml',
            $dataset['reference_date'],
            now()->format('Ymd-His')
        );

        return $this->exportXml('hr_workforce_register', $dataset, $filename);
    }

    public function exportAccountingJournalLinesXml(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildJournalLinesDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-journal-lines-%s-%s.xml',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXml('payroll_journal_lines', $dataset, $filename);
    }

    private function exportCsv(string $filename, array $headers, array $rows)
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle) ?: '';
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    private function exportXml(string $rootElement, array $dataset, string $filename)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        $xml->startElement($rootElement);

        $xml->writeElement('reference_period', (string) ($dataset['reference_period'] ?? ''));
        $xml->writeElement('period_start', (string) ($dataset['period_start'] ?? ''));
        $xml->writeElement('period_end', (string) ($dataset['period_end'] ?? ''));

        $xml->startElement('summary');
        foreach (($dataset['summary'] ?? []) as $key => $value) {
            $xml->writeElement((string) $key, (string) $value);
        }
        $xml->endElement();

        $xml->startElement('rows');
        foreach (($dataset['rows'] ?? []) as $row) {
            $xml->startElement('row');
            foreach ($row as $key => $value) {
                $xml->writeElement((string) $key, (string) ($value ?? ''));
            }
            $xml->endElement();
        }
        $xml->endElement();

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}
