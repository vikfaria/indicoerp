<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Models\CostCenter;
use App\Models\Setting;
use App\Services\MozambiqueHrDisciplinaryReportService;
use App\Services\MozambiqueForeignWorkerComplianceReportService;
use App\Services\MozambiqueHrComplianceAlertService;
use App\Services\MozambiquePayrollAccountingExportService;
use App\Services\MozambiqueHrComplianceDashboardService;
use App\Services\MozambiqueHrLegalSettingsService;
use App\Services\MozambiqueHrAttendanceComplianceReportService;
use App\Services\MozambiqueHrLeaveComplianceReportService;
use App\Services\MozambiqueHrTrainingComplianceReportService;
use App\Services\MozambiqueHrWorkforceExportService;
use App\Services\MozambiqueLabourComplianceService;
use App\Services\MozambiquePayrollSubmissionReportService;
use App\Services\PayrollCostCenterAllocatorService;
use Carbon\Carbon;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Attendance;
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
        private readonly MozambiqueHrDisciplinaryReportService $disciplinaryReportService,
        private readonly MozambiqueHrAttendanceComplianceReportService $attendanceComplianceReportService,
        private readonly MozambiqueHrLeaveComplianceReportService $leaveComplianceReportService,
        private readonly MozambiqueHrTrainingComplianceReportService $trainingComplianceReportService,
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
            'attendanceDeviceConfig' => $this->buildAttendanceDeviceConfig($companyId),
            'attendanceDeviceHealth' => $this->buildAttendanceDeviceHealthDataset($companyId, 12),
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

    public function updateAttendanceDeviceSettings(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'token' => 'nullable|string|min:16|max:255',
            'default_device_label' => 'nullable|string|max:160',
        ]);

        $companyId = creatorId();
        $existingToken = trim((string) company_setting('mz_attendance_device_ingest_token'));
        $nextToken = trim((string) ($validated['token'] ?? ''));

        if (($validated['enabled'] ?? false) && $nextToken === '' && $existingToken === '') {
            return back()->withErrors([
                'token' => __('Device token is required when biometric ingest is enabled.'),
            ]);
        }

        if ($nextToken !== '') {
            setSetting('mz_attendance_device_ingest_token', $nextToken, null, false);
        }

        setSetting('mz_attendance_device_ingest_enabled', !empty($validated['enabled']) ? '1' : '0', null, false);

        if (array_key_exists('default_device_label', $validated)) {
            setSetting('mz_attendance_device_default_label', trim((string) $validated['default_device_label']));
        }

        return back()->with('success', __('Biometric attendance device settings updated successfully.'));
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
            'leave_entitlement_first_year_days' => 'nullable|integer|min:1|max:366',
            'leave_entitlement_following_year_days' => 'nullable|integer|min:1|max:366',
            'leave_entitlement_prorate_first_year' => 'nullable|boolean',
            'leave_unjustified_absence_penalty_per_day' => 'nullable|integer|min:0|max:60',
            'leave_unjustified_absence_max_penalty_days' => 'nullable|integer|min:0|max:366',
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
        if (array_key_exists('leave_entitlement_first_year_days', $validated)) {
            setSetting('mz_leave_entitlement_first_year_days', $validated['leave_entitlement_first_year_days'] ?? '');
        }
        if (array_key_exists('leave_entitlement_following_year_days', $validated)) {
            setSetting('mz_leave_entitlement_following_year_days', $validated['leave_entitlement_following_year_days'] ?? '');
        }
        if (array_key_exists('leave_entitlement_prorate_first_year', $validated)) {
            setSetting('mz_leave_entitlement_prorate_first_year', !empty($validated['leave_entitlement_prorate_first_year']) ? '1' : '0');
        }
        if (array_key_exists('leave_unjustified_absence_penalty_per_day', $validated)) {
            setSetting('mz_leave_unjustified_absence_penalty_per_day', $validated['leave_unjustified_absence_penalty_per_day'] ?? '');
        }
        if (array_key_exists('leave_unjustified_absence_max_penalty_days', $validated)) {
            setSetting('mz_leave_unjustified_absence_max_penalty_days', $validated['leave_unjustified_absence_max_penalty_days'] ?? '');
        }

        return back()->with('success', __('Mozambique labour policy updated successfully.'));
    }

    public function updateLegalSettings(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'company_profile' => 'required|array',
            'company_profile.sector_activity' => 'nullable|string|max:191',
            'company_profile.operation_province' => 'nullable|string|max:191',
            'company_profile.labour_regime' => 'nullable|string|max:191',
            'company_profile.collective_agreements' => 'nullable|string|max:255',
            'company_profile.labour_directorate' => 'nullable|string|max:191',
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
            'policy_requirements' => 'required|array',
            'policy_requirements.require_internal_regulation' => 'required|boolean',
            'policy_requirements.require_code_of_conduct' => 'required|boolean',
            'policy_requirements.require_anti_harassment_policy' => 'required|boolean',
            'policy_requirements.require_disciplinary_policy' => 'required|boolean',
            'policy_requirements.require_vacation_policy' => 'required|boolean',
            'policy_requirements.require_data_protection_policy' => 'required|boolean',
            'policy_requirements.require_equipment_use_policy' => 'required|boolean',
            'policy_requirements.require_remote_work_policy' => 'required|boolean',
            'policy_requirements.code_of_conduct_min_workers' => 'required|integer|min:1|max:100000',
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

        return $this->exportCsv(
            $filename,
            $this->modelo19Headers(),
            $this->buildModelo19Rows($dataset)
        );
    }

    public function exportModelo19SupportJson(Request $request)
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

        return response()->json($dataset);
    }

    public function exportModelo19SupportXml(Request $request)
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
            'modelo19-irps-support-%s-%s.xml',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXml('modelo19_irps_support', $dataset, $filename);
    }

    public function exportModelo19SupportXlsx(Request $request)
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
            'modelo19-irps-support-%s-%s.xlsx',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXlsx(
            $filename,
            $this->modelo19Headers(),
            $this->buildModelo19Rows($dataset)
        );
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

        return $this->exportCsv(
            $filename,
            $this->inssHeaders(),
            $this->buildInssRows($dataset)
        );
    }

    public function exportInssGuideJson(Request $request)
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

        return response()->json($dataset);
    }

    public function exportInssGuideXml(Request $request)
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
            'inss-monthly-guide-%s-%s.xml',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXml('inss_monthly_guide', $dataset, $filename);
    }

    public function exportInssGuideXlsx(Request $request)
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
            'inss-monthly-guide-%s-%s.xlsx',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXlsx(
            $filename,
            $this->inssHeaders(),
            $this->buildInssRows($dataset)
        );
    }

    private function modelo19Headers(): array
    {
        return [
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
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function buildModelo19Rows(array $dataset): array
    {
        return collect($dataset['rows'])->map(static function (array $row) use ($dataset): array {
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
    }

    private function inssHeaders(): array
    {
        return [
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
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function buildInssRows(array $dataset): array
    {
        return collect($dataset['rows'])->map(static function (array $row) use ($dataset): array {
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

        $maskSensitive = $this->shouldMaskSensitiveEmployeeData();
        $rows = collect($dataset['rows'])->map(function (array $row) use ($maskSensitive): array {
            $employeeNuit = $maskSensitive
                ? $this->maskIdentifier((string) ($row['employee_nuit'] ?? ''), 2, 2)
                : ($row['employee_nuit'] ?? '');
            $accountHolderName = $maskSensitive
                ? $this->maskPersonName((string) ($row['account_holder_name'] ?? ''))
                : ($row['account_holder_name'] ?? '');
            $bankIdentifierCode = $maskSensitive
                ? $this->maskIdentifier((string) ($row['bank_identifier_code'] ?? ''), 2, 2)
                : ($row['bank_identifier_code'] ?? '');
            $accountNumber = $maskSensitive
                ? $this->maskIdentifier((string) ($row['account_number'] ?? ''), 0, 4)
                : ($row['account_number'] ?? '');

            return [
                $row['reference_period'],
                $row['payment_reference'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_date'],
                $row['employee_name'],
                $employeeNuit,
                $accountHolderName,
                $row['bank_name'],
                $row['bank_branch'],
                $bankIdentifierCode,
                $accountNumber,
                $row['currency'],
                $row['net_pay'],
                $row['payroll_entry_status'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportAnnualFiscalHistory(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'fiscal_year' => 'nullable|digits:4|integer|min:2000|max:2100',
        ]);

        $dataset = $this->payrollSubmissionReportService->buildAnnualFiscalHistoryDataset(
            creatorId(),
            isset($validated['fiscal_year']) ? (string) $validated['fiscal_year'] : null
        );

        $filename = sprintf(
            'payroll-annual-fiscal-history-%s-%s.csv',
            $dataset['fiscal_year'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Fiscal Year',
            'Period Start',
            'Period End',
            'Employee Internal ID',
            'Employee',
            'Employee NUIT',
            'Residency Status',
            'Payroll Runs',
            'Payroll Entries',
            'Latest Pay Date',
            'Gross Pay Total',
            'Taxable Income Total',
            'Taxable Benefits Total',
            'IRPS Withheld Total',
            'INSS Employee Total',
            'INSS Employer Total',
            'Net Pay Total',
            'Eligible Dependents Total',
            'Effective Dependents Total',
            'Average Effective Dependents',
            'Dependent Deduction Total',
            'Adjusted Taxable Income Total',
        ];

        $maskSensitive = $this->shouldMaskSensitiveEmployeeData();
        $rows = collect($dataset['rows'])->map(function (array $row) use ($dataset, $maskSensitive): array {
            $employeeNuit = $maskSensitive
                ? $this->maskIdentifier((string) ($row['employee_nuit'] ?? ''), 2, 2)
                : ($row['employee_nuit'] ?? '');

            return [
                $row['fiscal_year'],
                $dataset['period_start'],
                $dataset['period_end'],
                $row['employee_internal_id'],
                $row['employee_name'],
                $employeeNuit,
                $row['residency_status'],
                $row['payroll_runs'],
                $row['payroll_entries'],
                $row['latest_pay_date'],
                $row['gross_pay_total'],
                $row['taxable_income_total'],
                $row['taxable_benefits_total'],
                $row['irps_withheld_total'],
                $row['inss_employee_total'],
                $row['inss_employer_total'],
                $row['net_pay_total'],
                $row['eligible_dependents_total'],
                $row['effective_dependents_total'],
                $row['average_effective_dependents'],
                $row['dependent_deduction_total'],
                $row['adjusted_taxable_income_total'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportAnnualFiscalHistoryJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'fiscal_year' => 'nullable|digits:4|integer|min:2000|max:2100',
        ]);

        $dataset = $this->payrollSubmissionReportService->buildAnnualFiscalHistoryDataset(
            creatorId(),
            isset($validated['fiscal_year']) ? (string) $validated['fiscal_year'] : null
        );

        if ($this->shouldMaskSensitiveEmployeeData()) {
            $dataset['rows'] = collect((array) ($dataset['rows'] ?? []))
                ->map(function (array $row): array {
                    $row['employee_nuit'] = $this->maskIdentifier((string) ($row['employee_nuit'] ?? ''), 2, 2);

                    return $row;
                })
                ->values()
                ->all();
        }

        return response()->json($dataset);
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

        $maskSensitive = $this->shouldMaskSensitiveEmployeeData();
        $rows = $detailRows->map(function (array $row) use ($dataset, $summary, $quota, $maskSensitive): array {
            $employeeNuit = $maskSensitive
                ? $this->maskIdentifier((string) ($row['employee_nuit'] ?? ''), 2, 2)
                : ($row['employee_nuit'] ?? '');
            $passportNumber = $maskSensitive
                ? $this->maskIdentifier((string) ($row['passport_number'] ?? ''), 1, 2)
                : ($row['passport_number'] ?? '');
            $workAuthorizationNumber = $maskSensitive
                ? $this->maskIdentifier((string) ($row['work_authorization_number'] ?? ''), 1, 2)
                : ($row['work_authorization_number'] ?? '');

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
                $employeeNuit,
                $row['nationality'] ?? '',
                $row['residency_status'] ?? '',
                $row['hiring_regime'] ?? '',
                $row['work_province'] ?? '',
                $passportNumber,
                $row['passport_expires_at'] ?? '',
                $row['passport_status'] ?? '',
                $row['visa_type'] ?? '',
                $row['visa_expires_at'] ?? '',
                $row['visa_status'] ?? '',
                $workAuthorizationNumber,
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

    public function exportDisciplinaryCasesReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->disciplinaryReportService->buildDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'disciplinary-cases-report-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Case Type',
            'Case ID',
            'Case Reference',
            'Opened At',
            'Employee',
            'Employee NUIT',
            'Against Employee',
            'Category',
            'Severity',
            'Status',
            'Harassment Case',
            'Confidential',
            'Confidentiality Level',
            'Assigned Owner',
            'Warning Issued By',
            'Disciplinary Sanction',
            'Response Deadline',
            'Decision Deadline',
            'Decision / Resolution Date',
            'Response Deadline Overdue',
            'Decision Deadline Overdue',
            'Subject',
            'Description',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'] ?? '',
                $row['case_type'] ?? '',
                $row['case_id'] ?? '',
                $row['case_reference'] ?? '',
                $row['case_opened_at'] ?? '',
                $row['employee_name'] ?? '',
                $row['employee_nuit'] ?? '',
                $row['against_employee_name'] ?? '',
                $row['category_name'] ?? '',
                $row['severity'] ?? '',
                $row['status'] ?? '',
                !empty($row['is_harassment_case']) ? 'yes' : 'no',
                !empty($row['is_confidential']) ? 'yes' : 'no',
                $row['confidentiality_level'] ?? '',
                $row['assigned_owner'] ?? '',
                $row['warning_issued_by'] ?? '',
                $row['disciplinary_sanction'] ?? '',
                $row['response_deadline_at'] ?? '',
                $row['decision_deadline_at'] ?? '',
                $row['decision_or_resolution_date'] ?? '',
                !empty($row['response_deadline_overdue']) ? 'yes' : 'no',
                !empty($row['decision_deadline_overdue']) ? 'yes' : 'no',
                $row['subject'] ?? '',
                $row['description'] ?? '',
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportDisciplinaryCasesReportJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->disciplinaryReportService->buildDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportLeaveComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
        ]);

        $dataset = $this->leaveComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null
        );

        $filename = sprintf(
            'annual-leave-compliance-%s-%s.csv',
            $dataset['reference_date'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Date',
            'Reference Year',
            'Employee Record ID',
            'Employee Internal ID',
            'Employee',
            'Employee NUIT',
            'Date of Joining',
            'Branch',
            'Department',
            'Annual Leave Entitlement',
            'Annual Leave Taken',
            'Annual Leave Compensated',
            'Annual Leave Effective Rest',
            'Annual Leave Balance',
            'Annual Leave Overdue (Previous Year)',
            'Annual Leave Due by Year End',
            'Annual Leave Scheduled (Future)',
            'Annual Leave Planned (Year)',
            'Annual Leave Planned Approved',
            'Annual Leave Planned Pending',
            'Annual Leave Planned Rejected',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_date'] ?? '',
                $row['reference_year'] ?? '',
                $row['employee_record_id'] ?? '',
                $row['employee_internal_id'] ?? '',
                $row['employee_name'] ?? '',
                $row['employee_nuit'] ?? '',
                $row['date_of_joining'] ?? '',
                $row['branch'] ?? '',
                $row['department'] ?? '',
                $row['annual_leave_entitlement_days'] ?? 0,
                $row['annual_leave_taken_days'] ?? 0,
                $row['annual_leave_compensated_days'] ?? 0,
                $row['annual_leave_effective_rest_days'] ?? 0,
                $row['annual_leave_balance_days'] ?? 0,
                $row['annual_leave_overdue_days_previous_year'] ?? 0,
                $row['annual_leave_due_by_year_end_days'] ?? 0,
                $row['annual_leave_scheduled_future_days'] ?? 0,
                $row['annual_leave_planned_days_current_year'] ?? 0,
                $row['annual_leave_planned_approved_days_current_year'] ?? 0,
                $row['annual_leave_planned_pending_days_current_year'] ?? 0,
                $row['annual_leave_planned_rejected_days_current_year'] ?? 0,
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportLeaveComplianceReportJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
        ]);

        $dataset = $this->leaveComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportTrainingComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
            'window_days' => 'nullable|integer|min:1|max:365',
        ]);

        $dataset = $this->trainingComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null,
            (int) ($validated['window_days'] ?? 30)
        );

        $filename = sprintf(
            'mandatory-training-compliance-%s-%s.csv',
            $dataset['report_date'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Report Date',
            'Window Days',
            'Employee Record ID',
            'Employee Internal ID',
            'Employee',
            'Employee NUIT',
            'Training Type ID',
            'Training Type',
            'Training Compliance Code',
            'Certificate Validity Days',
            'Last Training ID',
            'Last Training Title',
            'Last Completion Date',
            'Certificate Expires At',
            'Days Until Expiry',
            'Compliance Status',
            'Status Reason',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['report_date'] ?? '',
                $row['window_days'] ?? 30,
                $row['employee_record_id'] ?? '',
                $row['employee_internal_id'] ?? '',
                $row['employee_name'] ?? '',
                $row['employee_nuit'] ?? '',
                $row['training_type_id'] ?? '',
                $row['training_type_name'] ?? '',
                $row['training_compliance_code'] ?? '',
                $row['certificate_validity_days'] ?? '',
                $row['last_training_id'] ?? '',
                $row['last_training_title'] ?? '',
                $row['last_completion_date'] ?? '',
                $row['certificate_expires_at'] ?? '',
                $row['days_until_expiry'] ?? '',
                $row['compliance_status'] ?? '',
                $row['status_reason'] ?? '',
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportTrainingComplianceReportJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_date' => 'nullable|date_format:Y-m-d',
            'window_days' => 'nullable|integer|min:1|max:365',
        ]);

        $dataset = $this->trainingComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_date'] ?? null,
            (int) ($validated['window_days'] ?? 30)
        );

        return response()->json($dataset);
    }

    public function exportAttendanceComplianceReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->attendanceComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'attendance-compliance-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Period Start',
            'Period End',
            'Attendance ID',
            'Attendance Date',
            'Employee',
            'Shift',
            'Status',
            'Absence Category',
            'Justified Absence',
            'Unjustified Absence',
            'Worked Hours',
            'Break Hours',
            'Overtime Hours',
            'Overtime Amount',
            'Late Minutes',
            'Early Exit Minutes',
            'Night Work',
            'Night Work Minutes',
            'Weekly Rest Breach Risk',
            'Anomaly Late Entry',
            'Anomaly Early Exit',
            'Anomaly Missing Clock Out',
            'Anomaly Excessive Hours',
            'Anomaly Clock Order',
            'Anomaly Weekly Rest',
            'Has Attendance Anomaly',
            'Notes',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'] ?? '',
                $row['period_start'] ?? '',
                $row['period_end'] ?? '',
                $row['attendance_id'] ?? '',
                $row['attendance_date'] ?? '',
                $row['employee_name'] ?? '',
                $row['shift_name'] ?? '',
                $row['status'] ?? '',
                $row['absence_category'] ?? '',
                !empty($row['justified_absence']) ? 'yes' : 'no',
                !empty($row['unjustified_absence']) ? 'yes' : 'no',
                $row['worked_hours'] ?? 0,
                $row['break_hours'] ?? 0,
                $row['overtime_hours'] ?? 0,
                $row['overtime_amount'] ?? 0,
                $row['late_minutes'] ?? 0,
                $row['early_exit_minutes'] ?? 0,
                !empty($row['night_work']) ? 'yes' : 'no',
                $row['night_work_minutes'] ?? 0,
                !empty($row['weekly_rest_breach_risk']) ? 'yes' : 'no',
                !empty($row['anomaly_late_entry']) ? 'yes' : 'no',
                !empty($row['anomaly_early_exit']) ? 'yes' : 'no',
                !empty($row['anomaly_missing_clock_out']) ? 'yes' : 'no',
                !empty($row['anomaly_excessive_hours']) ? 'yes' : 'no',
                !empty($row['anomaly_clock_order']) ? 'yes' : 'no',
                !empty($row['anomaly_weekly_rest']) ? 'yes' : 'no',
                !empty($row['has_attendance_anomaly']) ? 'yes' : 'no',
                $row['notes'] ?? '',
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportAttendanceComplianceReportJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->attendanceComplianceReportService->buildDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportAttendanceDeviceHealthReport(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $dataset = $this->buildAttendanceDeviceHealthDataset(
            creatorId(),
            (int) ($validated['limit'] ?? 100)
        );

        $filename = sprintf(
            'attendance-device-health-%s.csv',
            now()->format('Ymd-His')
        );

        $headers = [
            'Generated At',
            'Window Start',
            'Window Hours',
            'Last Event At',
            'Events Last 24h',
            'Unique Devices Last 24h',
            'Clock-ins Last 24h',
            'Clock-outs Last 24h',
            'Open Attendances',
            'Attendance ID',
            'Employee',
            'Attendance Date',
            'Action',
            'Clock In',
            'Clock Out',
            'Device ID',
            'Device Label',
            'Source Reference',
            'Last Activity At',
        ];

        $rows = collect($dataset['rows'] ?? [])->map(static function (array $row) use ($dataset): array {
            $summary = $dataset['summary'] ?? [];

            return [
                $dataset['generated_at'] ?? '',
                $dataset['window_start'] ?? '',
                $dataset['window_hours'] ?? 24,
                $summary['last_event_at'] ?? '',
                $summary['total_events_last_24h'] ?? 0,
                $summary['unique_devices_last_24h'] ?? 0,
                $summary['clockins_last_24h'] ?? 0,
                $summary['clockouts_last_24h'] ?? 0,
                $summary['open_attendances'] ?? 0,
                $row['attendance_id'] ?? '',
                $row['employee_name'] ?? '',
                $row['attendance_date'] ?? '',
                $row['action'] ?? '',
                $row['clock_in'] ?? '',
                $row['clock_out'] ?? '',
                $row['source_device_id'] ?? '',
                $row['source_device_label'] ?? '',
                $row['source_reference'] ?? '',
                $row['last_activity_at'] ?? '',
            ];
        })->values()->all();

        if (empty($rows)) {
            $rows[] = [
                $dataset['generated_at'] ?? '',
                $dataset['window_start'] ?? '',
                $dataset['window_hours'] ?? 24,
                $dataset['summary']['last_event_at'] ?? '',
                $dataset['summary']['total_events_last_24h'] ?? 0,
                $dataset['summary']['unique_devices_last_24h'] ?? 0,
                $dataset['summary']['clockins_last_24h'] ?? 0,
                $dataset['summary']['clockouts_last_24h'] ?? 0,
                $dataset['summary']['open_attendances'] ?? 0,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportAttendanceDeviceHealthReportJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $dataset = $this->buildAttendanceDeviceHealthDataset(
            creatorId(),
            (int) ($validated['limit'] ?? 50)
        );

        return response()->json($dataset);
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
            'Business Unit',
            'Worksite',
            'Cost Center Code',
            'Cost Center Name',
            'Project',
            'Client',
            'Allocation Source',
            'Allocation Minutes',
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
                $row['business_unit'],
                $row['worksite'],
                $row['cost_center_code'],
                $row['cost_center_name'],
                $row['project_name'],
                $row['client_name'],
                $row['allocation_source'],
                $row['allocation_minutes'],
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

    public function exportCostAllocationXlsx(Request $request)
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
            'payroll-cost-allocation-%s-%s.xlsx',
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
            'Business Unit',
            'Worksite',
            'Cost Center Code',
            'Cost Center Name',
            'Project',
            'Client',
            'Allocation Source',
            'Allocation Minutes',
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
                $row['business_unit'],
                $row['worksite'],
                $row['cost_center_code'],
                $row['cost_center_name'],
                $row['project_name'],
                $row['client_name'],
                $row['allocation_source'],
                $row['allocation_minutes'],
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

        return $this->exportXlsx($filename, $headers, $rows);
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

    public function exportAccountingJournalLinesXlsx(Request $request)
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
            'payroll-journal-lines-%s-%s.xlsx',
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

        return $this->exportXlsx($filename, $headers, $rows);
    }

    public function exportPayrollMonthlySummary(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildMonthlyPayrollSummaryDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-monthly-summary-%s-%s.csv',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Payroll ID',
            'Payroll Title',
            'Pay Period Start',
            'Pay Period End',
            'Pay Date',
            'Payment Status',
            'Active Entries',
            'Employee Count',
            'Gross Pay Total',
            'Deductions Total',
            'Net Pay Total',
            'IRPS Total',
            'INSS Employee Total',
            'INSS Employer Total',
            'Allowances Total',
            'Manual Overtime Total',
            'Loans Total',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_period_start'],
                $row['pay_period_end'],
                $row['pay_date'],
                $row['payment_status'],
                $row['active_entries_count'],
                $row['employee_count'],
                $row['gross_pay_total'],
                $row['deductions_total'],
                $row['net_pay_total'],
                $row['irps_total'],
                $row['inss_employee_total'],
                $row['inss_employer_total'],
                $row['allowances_total'],
                $row['manual_overtime_total'],
                $row['loans_total'],
            ];
        })->values()->all();

        return $this->exportCsv($filename, $headers, $rows);
    }

    public function exportPayrollMonthlySummaryJson(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildMonthlyPayrollSummaryDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        return response()->json($dataset);
    }

    public function exportPayrollMonthlySummaryXml(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildMonthlyPayrollSummaryDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-monthly-summary-%s-%s.xml',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        return $this->exportXml('payroll_monthly_summary', $dataset, $filename);
    }

    public function exportPayrollMonthlySummaryXlsx(Request $request)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'reference_period' => 'nullable|date_format:Y-m',
        ]);

        $dataset = $this->payrollAccountingExportService->buildMonthlyPayrollSummaryDataset(
            creatorId(),
            $validated['reference_period'] ?? null
        );

        $filename = sprintf(
            'payroll-monthly-summary-%s-%s.xlsx',
            $dataset['reference_period'],
            now()->format('Ymd-His')
        );

        $headers = [
            'Reference Period',
            'Payroll ID',
            'Payroll Title',
            'Pay Period Start',
            'Pay Period End',
            'Pay Date',
            'Payment Status',
            'Active Entries',
            'Employee Count',
            'Gross Pay Total',
            'Deductions Total',
            'Net Pay Total',
            'IRPS Total',
            'INSS Employee Total',
            'INSS Employer Total',
            'Allowances Total',
            'Manual Overtime Total',
            'Loans Total',
        ];

        $rows = collect($dataset['rows'])->map(static function (array $row): array {
            return [
                $row['reference_period'],
                $row['payroll_id'],
                $row['payroll_title'],
                $row['pay_period_start'],
                $row['pay_period_end'],
                $row['pay_date'],
                $row['payment_status'],
                $row['active_entries_count'],
                $row['employee_count'],
                $row['gross_pay_total'],
                $row['deductions_total'],
                $row['net_pay_total'],
                $row['irps_total'],
                $row['inss_employee_total'],
                $row['inss_employer_total'],
                $row['allowances_total'],
                $row['manual_overtime_total'],
                $row['loans_total'],
            ];
        })->values()->all();

        return $this->exportXlsx($filename, $headers, $rows);
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
        if ($this->shouldMaskSensitiveEmployeeData()) {
            $dataset = $this->maskWorkforceDataset($dataset);
        }

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

    public function importAttendanceRegister(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $summary = $this->workforceExportService->importAttendanceCsv(
            creatorId(),
            $validated['csv_file']->getRealPath() ?: '',
            Auth::id()
        );

        if (($summary['processed'] ?? 0) === 0 && !empty($summary['errors'])) {
            return back()->with('error', $summary['errors'][0]['message'] ?? __('Attendance import failed.'));
        }

        $errorCount = count($summary['errors'] ?? []);
        $message = __('Attendance import completed. Processed: :processed, Updated: :updated, Skipped: :skipped.', [
            'processed' => (int) ($summary['processed'] ?? 0),
            'updated' => (int) ($summary['updated'] ?? 0),
            'skipped' => (int) ($summary['skipped'] ?? 0),
        ]);

        if ($errorCount > 0) {
            $message .= ' ' . __('Rows with issues: :count.', ['count' => $errorCount]);
        }

        return back()->with('success', $message);
    }

    public function importAnnualLeavePlans(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $summary = $this->workforceExportService->importAnnualLeavePlansCsv(
            creatorId(),
            $validated['csv_file']->getRealPath() ?: '',
            Auth::id()
        );

        if (($summary['processed'] ?? 0) === 0 && !empty($summary['errors'])) {
            return back()->with('error', $summary['errors'][0]['message'] ?? __('Annual leave plans import failed.'));
        }

        $errorCount = count($summary['errors'] ?? []);
        $message = __('Annual leave plans import completed. Processed: :processed, Updated: :updated, Skipped: :skipped.', [
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
        if ($this->shouldMaskSensitiveEmployeeData()) {
            $dataset = $this->maskWorkforceDataset($dataset);
        }

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
        if ($this->shouldMaskSensitiveEmployeeData()) {
            $dataset = $this->maskWorkforceDataset($dataset);
        }

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

    private function buildAttendanceDeviceConfig(int $companyId): array
    {
        $settings = Setting::query()
            ->where('created_by', $companyId)
            ->whereIn('key', [
                'mz_attendance_device_ingest_enabled',
                'mz_attendance_device_ingest_token',
                'mz_attendance_device_default_label',
            ])
            ->get()
            ->keyBy('key');

        /** @var Setting|null $tokenSetting */
        $tokenSetting = $settings->get('mz_attendance_device_ingest_token');
        $tokenValue = trim((string) ($tokenSetting?->value ?? ''));

        /** @var Setting|null $enabledSetting */
        $enabledSetting = $settings->get('mz_attendance_device_ingest_enabled');
        $enabled = $enabledSetting
            ? $this->parseBooleanSetting($enabledSetting->value)
            : ($tokenValue !== '');

        /** @var Setting|null $labelSetting */
        $labelSetting = $settings->get('mz_attendance_device_default_label');
        $defaultLabel = trim((string) ($labelSetting?->value ?? ''));

        return [
            'enabled' => $enabled,
            'has_token' => $tokenValue !== '',
            'token_preview' => $this->maskSensitiveToken($tokenValue),
            'token_updated_at' => $tokenSetting?->updated_at?->toIso8601String(),
            'default_device_label' => $defaultLabel !== '' ? $defaultLabel : null,
            'ingest_url' => url('/api/hrm/attendance/device-ingest'),
        ];
    }

    private function buildAttendanceDeviceHealthDataset(int $companyId, int $limit = 50): array
    {
        $now = now();
        $windowStart = $now->copy()->subDay();
        $safeLimit = max(1, min($limit, 200));

        $baseQuery = Attendance::query()
            ->active()
            ->where('created_by', $companyId)
            ->where('source_channel', 'biometric');

        $lastEventAt = (clone $baseQuery)->max('updated_at');
        $windowQuery = (clone $baseQuery)->where(function ($query) use ($windowStart): void {
            $query->where('created_at', '>=', $windowStart)->orWhere('updated_at', '>=', $windowStart);
        });

        $totalEventsLast24h = (clone $windowQuery)->count();
        $uniqueDevicesLast24h = (clone $windowQuery)
            ->whereNotNull('source_device_id')
            ->where('source_device_id', '<>', '')
            ->distinct('source_device_id')
            ->count('source_device_id');

        $clockinsLast24h = (clone $baseQuery)
            ->whereNotNull('clock_in')
            ->where('clock_in', '>=', $windowStart)
            ->count();
        $clockoutsLast24h = (clone $baseQuery)
            ->whereNotNull('clock_out')
            ->where('clock_out', '>=', $windowStart)
            ->count();

        $openAttendances = (clone $baseQuery)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->count();

        $rows = (clone $baseQuery)
            ->with('user:id,name')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($safeLimit)
            ->get([
                'id',
                'employee_id',
                'date',
                'clock_in',
                'clock_out',
                'source_device_id',
                'source_device_label',
                'source_reference',
                'created_at',
                'updated_at',
            ])
            ->map(static function (Attendance $attendance): array {
                $updatedAt = $attendance->updated_at instanceof Carbon
                    ? $attendance->updated_at
                    : ($attendance->updated_at ? Carbon::parse((string) $attendance->updated_at) : null);
                $createdAt = $attendance->created_at instanceof Carbon
                    ? $attendance->created_at
                    : ($attendance->created_at ? Carbon::parse((string) $attendance->created_at) : null);

                $action = 'clockin';
                if ($attendance->clock_out) {
                    if ($updatedAt && $createdAt && $updatedAt->gt($createdAt)) {
                        $action = 'clockout';
                    } else {
                        $action = 'clockin_and_clockout';
                    }
                }

                return [
                    'attendance_id' => (int) $attendance->id,
                    'employee_name' => $attendance->user?->name ?? ('User #' . (int) $attendance->employee_id),
                    'attendance_date' => $attendance->date?->format('Y-m-d'),
                    'action' => $action,
                    'clock_in' => $attendance->clock_in ? Carbon::parse((string) $attendance->clock_in)->toDateTimeString() : null,
                    'clock_out' => $attendance->clock_out ? Carbon::parse((string) $attendance->clock_out)->toDateTimeString() : null,
                    'source_device_id' => $attendance->source_device_id,
                    'source_device_label' => $attendance->source_device_label,
                    'source_reference' => $attendance->source_reference,
                    'last_activity_at' => $updatedAt?->toIso8601String() ?? $createdAt?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return [
            'generated_at' => $now->toIso8601String(),
            'window_start' => $windowStart->toIso8601String(),
            'window_hours' => 24,
            'summary' => [
                'total_events_last_24h' => $totalEventsLast24h,
                'unique_devices_last_24h' => $uniqueDevicesLast24h,
                'clockins_last_24h' => $clockinsLast24h,
                'clockouts_last_24h' => $clockoutsLast24h,
                'open_attendances' => $openAttendances,
                'last_event_at' => $lastEventAt ? Carbon::parse((string) $lastEventAt)->toIso8601String() : null,
            ],
            'rows' => $rows,
        ];
    }

    private function parseBooleanSetting(mixed $value): bool
    {
        $normalized = strtolower(trim((string) ($value ?? '')));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function shouldMaskSensitiveEmployeeData(): bool
    {
        return !Auth::user()->can('view-sensitive-employee-data');
    }

    private function maskSensitiveToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        $length = strlen($token);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($token, 0, 4)
            . str_repeat('*', max(4, $length - 8))
            . substr($token, -4);
    }

    private function maskIdentifier(string $value, int $visiblePrefix = 2, int $visibleSuffix = 2): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $length = strlen($clean);
        if ($length <= ($visiblePrefix + $visibleSuffix)) {
            return str_repeat('*', $length);
        }

        $maskedLength = max(4, $length - ($visiblePrefix + $visibleSuffix));

        return substr($clean, 0, $visiblePrefix)
            . str_repeat('*', $maskedLength)
            . substr($clean, -$visibleSuffix);
    }

    private function maskPersonName(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        $firstChar = substr($clean, 0, 1);

        return sprintf('%s%s', $firstChar, str_repeat('*', max(3, strlen($clean) - 1)));
    }

    private function maskWorkforceDataset(array $dataset): array
    {
        $dataset['rows'] = collect((array) ($dataset['rows'] ?? []))
            ->map(function (array $row): array {
                $row['employee_nuit'] = $this->maskIdentifier((string) ($row['employee_nuit'] ?? ''), 2, 2);
                $row['inss_number'] = $this->maskIdentifier((string) ($row['inss_number'] ?? ''), 2, 2);
                $row['passport_number'] = $this->maskIdentifier((string) ($row['passport_number'] ?? ''), 1, 2);
                $row['work_authorization_number'] = $this->maskIdentifier((string) ($row['work_authorization_number'] ?? ''), 1, 2);

                return $row;
            })
            ->values()
            ->all();

        return $dataset;
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

    private function exportXlsx(string $filename, array $headers, array $rows)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray($headers, null, 'A1');

        if (!empty($rows)) {
            $worksheet->fromArray($rows, null, 'A2');
        }

        $writer = new Xlsx($spreadsheet);
        $tempPath = tempnam(sys_get_temp_dir(), 'hrm-xlsx-');
        if ($tempPath === false) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return response('', 500);
        }

        $writer->save($tempPath);
        $xlsxContent = file_get_contents($tempPath) ?: '';
        @unlink($tempPath);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}
