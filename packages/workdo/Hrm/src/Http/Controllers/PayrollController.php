<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiquePayrollTaxService;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Http\Requests\StorePayrollRequest;
use Workdo\Hrm\Http\Requests\UpdatePayrollRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Workdo\Hrm\Models\Allowance;
use Workdo\Hrm\Models\AllowanceType;
use Workdo\Hrm\Models\Attendance;
use Workdo\Hrm\Models\Deduction;
use Workdo\Hrm\Models\Employee;
use Workdo\Hrm\Models\LeaveApplication;
use Workdo\Hrm\Models\Loan;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\PayrollEntry;
use Workdo\Hrm\Events\CreatePayroll;
use Workdo\Hrm\Events\UpdatePayroll;
use Workdo\Hrm\Events\DestroyPayroll;
use Workdo\Hrm\Events\DestroySalarySlip;
use Workdo\Hrm\Events\PaySalary;

class PayrollController extends Controller
{
    public function __construct(private readonly MozambiquePayrollTaxService $mozambiquePayrollTaxService)
    {
    }

    private function checkPayrollAccess(Payroll $payroll)
    {
        if(Auth::user()->can('manage-any-payrolls')) {
            return $payroll->created_by == creatorId();
        } elseif(Auth::user()->can('manage-own-payrolls')) {
            return $payroll->creator_id == Auth::id();
        }
        return false;
    }
    public function index()
    {
        if (Auth::user()->can('manage-payrolls')) {
            $payrolls = Payroll::query()

                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-payrolls')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-payrolls')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('title', 'like', '%' . request('title') . '%');
                    });
                })
                ->when(request('payroll_frequency') !== null && request('payroll_frequency') !== '', fn($q) => $q->where('payroll_frequency', request('payroll_frequency')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Payrolls/Index', [
                'payrolls' => $payrolls,

            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePayrollRequest $request)
    {
        if (Auth::user()->can('create-payrolls')) {
            $validated = $request->validated();
            $payroll = new Payroll();
            $payroll->title = $validated['title'];
            $payroll->payroll_frequency = $validated['payroll_frequency'];
            $payroll->pay_period_start = $validated['pay_period_start'];
            $payroll->pay_period_end = $validated['pay_period_end'];
            $payroll->pay_date = $validated['pay_date'];
            $payroll->notes = $validated['notes'];

            $payroll->creator_id = Auth::id();
            $payroll->created_by = creatorId();
            $payroll->save();

            CreatePayroll::dispatch($request, $payroll);

            return redirect()->route('hrm.payrolls.index')->with('success', __('The payroll has been created successfully.'));
        } else {
            return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll)
    {
        if (Auth::user()->can('edit-payrolls')) {
            if (!$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }

            if ($payroll->status === 'cancelled') {
                return redirect()->back()->with('error', __('Cancelled payroll cannot be edited.'));
            }

            $validated = $request->validated();
            $payroll->title = $validated['title'];
            $payroll->payroll_frequency = $validated['payroll_frequency'];
            $payroll->pay_period_start = $validated['pay_period_start'];
            $payroll->pay_period_end = $validated['pay_period_end'];
            $payroll->pay_date = $validated['pay_date'];
            $payroll->notes = $validated['notes'];
            $payroll->status = $validated['status'];
            $payroll->is_payroll_paid = $validated['is_payroll_paid'] ?? 'unpaid';
            $payroll->save();

            UpdatePayroll::dispatch($request, $payroll);

            return redirect()->back()->with('success', __('The payroll details are updated successfully.'));
        } else {
            return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Payroll $payroll)
    {
        if (Auth::user()->can('view-payrolls')) {
            if(!$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }
            $payroll->load(['payrollEntries' => function ($query) {
                $query->where(function ($statusQuery): void {
                    $statusQuery->whereNull('is_cancelled')->orWhere('is_cancelled', false);
                });

                if (Auth::user()->can('view-any-payrolls')) {
                    $query->with('employee.user')->where('created_by', creatorId());
                } elseif (Auth::user()->can('view-own-payrolls')) {
                    $query->with('employee.user')->where('employee_id', Auth::id());
                } else {
                    $query->whereRaw('1 = 0');
                }
            }]);

            return Inertia::render('Hrm/Payrolls/Show', [
                'payroll' => $payroll,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Payroll $payroll)
    {
        if (Auth::user()->can('delete-payrolls')) {
            if (!$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }

            if ($payroll->status === 'cancelled') {
                return redirect()->back()->with('error', __('Payroll is already cancelled.'));
            }

            if (($payroll->is_payroll_paid ?? 'unpaid') === 'paid') {
                return redirect()->back()->with('error', __('Paid payrolls cannot be cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroyPayroll::dispatch($payroll);
            $payroll->update([
                'status' => 'cancelled',
                'is_payroll_paid' => 'unpaid',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            PayrollEntry::query()
                ->where('payroll_id', $payroll->id)
                ->where(function ($query): void {
                    $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
                })
                ->update([
                    'status' => 'unpaid',
                    'is_cancelled' => true,
                    'cancelled_at' => now(),
                    'cancelled_by' => Auth::id(),
                    'cancellation_reason' => trim((string) $validated['cancellation_reason']),
                ]);

            return redirect()->back()->with('success', __('Payroll cancelled successfully.'));
        } else {
            return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
        }
    }

    public function runPayroll(Payroll $payroll)
    {
        if (Auth::user()->can('run-payrolls')) {
            if (!$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }

            if ($payroll->status === 'cancelled') {
                return redirect()->back()->with('error', __('Cancelled payroll cannot be processed.'));
            }

            try {
                $payroll->update(['status' => 'processing']);

                // Get working days from settings
                $globalSettings = getCompanyAllSetting();
                $workingDaysIndices = json_decode($globalSettings['working_days'] ?? '[]', true);

                if (empty($workingDaysIndices)) {
                    $payroll->update(['status' => 'draft']);
                    return redirect()->back()->with('error', __('Please configure working days first.'));
                }

                // Calculate working days in pay period
                $startDate = new \DateTime($payroll->pay_period_start);
                $endDate = new \DateTime($payroll->pay_period_end);
                $workingDaysCount = 0;

                for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
                    $dayIndex = (int) $date->format('w');
                    if (in_array($dayIndex, $workingDaysIndices)) {
                        $workingDaysCount++;
                    }
                }


                // Get all employees
                $employees = Employee::with(['user', 'foreignWorkerProfile', 'dependents'])
                    ->where('created_by', creatorId())
                    ->get();
                $legalSetupCheck = $this->validateMozambiquePayrollLegalSetup($payroll, $employees);
                if (!$legalSetupCheck['valid']) {
                    $payroll->update(['status' => 'draft']);
                    return redirect()->back()->with('error', $legalSetupCheck['message']);
                }

                $newEntriesCount = 0;

                foreach ($employees as $employee) {
                    // Skip employees with active payslips already calculated.
                    $existingEntry = PayrollEntry::query()
                        ->where('payroll_id', $payroll->id)
                        ->where('employee_id', $employee->user_id)
                        ->where(function ($query): void {
                            $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
                        })
                        ->first();

                    if (!$existingEntry) {
                        $this->processEmployeePayroll($payroll, $employee, $workingDaysCount, $startDate, $endDate);
                        $newEntriesCount++;
                    }
                }

                $entries = $this->activeEntriesQuery($payroll)->get();
                $totals = $this->computePayrollTotals($entries);

                $payroll->update([
                    'status' => 'completed',
                    'is_payroll_paid' => $totals['all_paid'] ? 'paid' : 'unpaid',
                    'cancelled_at' => null,
                    'cancelled_by' => null,
                    'cancellation_reason' => null,
                    ...$totals['values'],
                ]);

                if ($newEntriesCount > 0) {
                    return redirect()->back()->with('success', __('Payroll processed successfully. New payslips created for :new employees. Total employees: :total', [
                        'new' => $newEntriesCount,
                        'total' => $totals['values']['employee_count'],
                    ]));
                } else {
                    return redirect()->back()->with('error', __('Payroll already processed. All employee payslips are created. Total employees: :count', [
                        'count' => $totals['values']['employee_count'],
                    ]));
                }
            } catch (\Exception $e) {
                $payroll->update(['status' => 'draft']);
                return redirect()->back()->with('error', __('Failed to process payroll: :error', ['error' => $e->getMessage()]));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    private function validateMozambiquePayrollLegalSetup(Payroll $payroll, $employees): array
    {
        $requiredTables = ['mz_irps_tables', 'mz_irps_brackets', 'mz_inss_rates', 'mz_minimum_wages'];
        $missingTables = array_values(array_filter(
            $requiredTables,
            static fn(string $table): bool => !Schema::hasTable($table)
        ));

        if ($missingTables !== []) {
            return [
                'valid' => false,
                'message' => __('Payroll compliance tables are missing: :tables. Run migrations before processing payroll.', [
                    'tables' => implode(', ', $missingTables),
                ]),
            ];
        }

        $companyId = creatorId();
        $referenceDate = $payroll->pay_date ?: $payroll->pay_period_end ?: now()->toDateString();
        $effectiveDate = \Illuminate\Support\Carbon::parse((string) $referenceDate)->toDateString();

        $resolveActiveDateFilter = static function ($query) use ($effectiveDate): void {
            $query
                ->whereDate('effective_from', '<=', $effectiveDate)
                ->where(function ($dateQuery) use ($effectiveDate): void {
                    $dateQuery->whereNull('effective_to')->orWhereDate('effective_to', '>=', $effectiveDate);
                });
        };

        $activeIrpsTable = MozIrpsTable::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->where('is_active', true)
            ->where($resolveActiveDateFilter)
            ->orderByRaw('CASE WHEN created_by IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_from')
            ->first();

        $issues = [];

        if (!$activeIrpsTable) {
            $issues[] = __('No active IRPS table found for date :date.', ['date' => $effectiveDate]);
        } else {
            $hasBrackets = MozIrpsBracket::query()
                ->where('irps_table_id', $activeIrpsTable->id)
                ->exists();

            if (!$hasBrackets) {
                $issues[] = __('Active IRPS table ":name" has no brackets configured.', ['name' => $activeIrpsTable->name]);
            }
        }

        $hasActiveInss = MozInssRate::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->where('is_active', true)
            ->where($resolveActiveDateFilter)
            ->exists();

        if (!$hasActiveInss) {
            $issues[] = __('No active INSS rate found for date :date.', ['date' => $effectiveDate]);
        }

        $minimumWages = MozMinimumWage::query()
            ->where(function ($query) use ($companyId): void {
                $query->where('created_by', $companyId)->orWhereNull('created_by');
            })
            ->where('is_active', true)
            ->where($resolveActiveDateFilter)
            ->get();

        if ($minimumWages->isEmpty()) {
            $issues[] = __('No active minimum wage setup found for date :date.', ['date' => $effectiveDate]);
        } else {
            $configuredSectors = $minimumWages
                ->pluck('sector_code')
                ->filter()
                ->map(static fn($value): string => strtoupper(trim((string) $value)))
                ->unique()
                ->values();

            $hasGeneralSector = $configuredSectors->contains('GENERAL');

            if (!$hasGeneralSector) {
                $employeeSectors = collect($employees)
                    ->pluck('employment_type')
                    ->map(static fn($value): string => strtoupper(trim((string) $value)))
                    ->filter()
                    ->unique()
                    ->values();

                $missingSectors = $employeeSectors
                    ->filter(static fn(string $sector): bool => !$configuredSectors->contains($sector))
                    ->values();

                if ($missingSectors->isNotEmpty()) {
                    $issues[] = __('Minimum wage setup missing for sectors: :sectors', [
                        'sectors' => $missingSectors->implode(', '),
                    ]);
                }
            }
        }

        if ($issues !== []) {
            return [
                'valid' => false,
                'message' => __('Payroll legal setup is incomplete. :details', [
                    'details' => implode(' ', $issues),
                ]),
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    private function processEmployeePayroll($payroll, $employee, $workingDaysCount, $startDate, $endDate)
    {
        // Get employee basic salary
        $basicSalary = $employee->basic_salary ?? 0;
        $perDaySalary = $workingDaysCount > 0 ? $basicSalary / $workingDaysCount : 0;

        // Calculate allowances, deductions, overtimes and loans
        $allowanceData = $this->calculateAllowances($employee, $basicSalary);
        $deductionData = $this->calculateDeductions($employee, $basicSalary);
        $manualOvertimeData = $this->calculateOvertimes($employee, $basicSalary, $startDate, $endDate);
        $loanData = $this->calculateLoans($employee, $basicSalary, $startDate, $endDate);


        // Allowance breakdown
        $allowancesBreakdown = $allowanceData['breakdown'];
        $totalAllowances = $allowanceData['total'];

        // deduction breakdown
        $deductionsBreakdown = $deductionData['breakdown'];
        $totalDeductions = $deductionData['total'];

        // manual overtime breakdown
        $manualOvertimesBreakdown = $manualOvertimeData['breakdown'];
        $totalManualOvertimes = $manualOvertimeData['total'];
        $totalManualOvertimeHour =  $manualOvertimeData['totalManualOvertimeHour'];

        // loan breakdown
        $loansBreakdown = $loanData['breakdown'];
        $totalLoans = $loanData['total'];


        // Calculate attendance data
        $attendanceData = $this->calculateAttendance($employee, $startDate, $endDate);

        $presentDays = $attendanceData['present_days'];
        $halfDays = $attendanceData['half_days'];
        $absentDays = $attendanceData['absent_days'];
        $overtimeHours = $attendanceData['overtime_hours'];
        $overtimeAmount = $attendanceData['overtime_amount'];


        // Calculate leave data
        $leaveData = $this->calculateLeave($employee, $startDate, $endDate);

        $paidLeaveDays = $leaveData['paid_leave_days'];
        $unpaidLeaveDays = $leaveData['unpaid_leave_days'];

        $totalAllOverTimeHours =  $totalManualOvertimeHour + $overtimeHours;
        // Calculate final salary
        $totalEarnings = $basicSalary + $totalAllowances + $totalManualOvertimes;
        $halfDayDeduction = $perDaySalary * ($halfDays * 0.5);
        $absentDayDeduction = $perDaySalary * $absentDays;
        $unpaidLeaveDeduction = $perDaySalary * $unpaidLeaveDays;
        $totalLeaveSalaryDeductions = $unpaidLeaveDeduction + $halfDayDeduction + $absentDayDeduction;
        $grossPay = $totalEarnings - $totalLeaveSalaryDeductions + $overtimeAmount; // overtimeAmount is from attendance

        $taxDate = $payroll->pay_date ?? $endDate->format('Y-m-d');
        $companyId = creatorId();
        $taxDateCarbon = \Illuminate\Support\Carbon::parse((string) $taxDate)->startOfDay();
        $residencyStatus = strtolower((string) ($employee->foreignWorkerProfile?->residency_status ?? 'resident'));
        if (!in_array($residencyStatus, ['resident', 'non_resident'], true)) {
            $residencyStatus = 'resident';
        }

        $eligibleDependentsCount = collect($employee->dependents ?? [])
            ->filter(function ($dependent) use ($taxDateCarbon): bool {
                if (!$dependent || !$dependent->is_tax_eligible) {
                    return false;
                }

                if ($dependent->valid_until === null) {
                    return true;
                }

                return \Illuminate\Support\Carbon::parse((string) $dependent->valid_until)
                    ->endOfDay()
                    ->greaterThanOrEqualTo($taxDateCarbon);
            })
            ->count();

        $irpsData = $this->mozambiquePayrollTaxService->calculateIrps(
            (float) $grossPay,
            $companyId,
            $taxDate,
            [
                'residency_status' => $residencyStatus,
                'eligible_dependents_count' => $eligibleDependentsCount,
            ]
        );
        $inssData = $this->mozambiquePayrollTaxService->calculateInss((float) $grossPay, $companyId, $taxDate);

        $sectorCode = $employee->employment_type ? strtoupper((string) $employee->employment_type) : 'GENERAL';
        $minimumWageData = $this->mozambiquePayrollTaxService->validateMinimumWage($sectorCode, (float) $basicSalary, $companyId, $taxDate);

        $statutoryDeductionsBreakdown = [];
        if ((float) $irpsData['irps_amount'] > 0) {
            $statutoryDeductionsBreakdown['IRPS'] = (float) $irpsData['irps_amount'];
        }

        if ((float) $inssData['employee_amount'] > 0) {
            $label = sprintf('INSS Trabalhador (%.2f%%)', (float) $inssData['employee_rate']);
            $statutoryDeductionsBreakdown[$label] = (float) $inssData['employee_amount'];
        }

        $statutoryDeductionsTotal = (float) $irpsData['irps_amount'] + (float) $inssData['employee_amount'];
        $totalDeductionsWithStatutory = $totalDeductions + $statutoryDeductionsTotal;
        $totalAllDeductions = $totalDeductionsWithStatutory + $totalLoans;
        $netPay = $grossPay - $totalAllDeductions;

        if ($statutoryDeductionsBreakdown !== []) {
            $deductionsBreakdown = array_merge($deductionsBreakdown, $statutoryDeductionsBreakdown);
        }

        // Upsert by unique payroll+employee key, reviving previously cancelled entries.
        PayrollEntry::updateOrCreate([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->user_id,
        ], [
            'basic_salary' => $basicSalary,
            'total_allowances' => $totalAllowances,

            'total_deductions' => $totalDeductionsWithStatutory,
            'total_loans' => $totalLoans,
            'taxable_income' => $grossPay,
            'irps_amount' => $irpsData['irps_amount'],
            'inss_employee_rate' => $inssData['employee_rate'],
            'inss_employee_amount' => $inssData['employee_amount'],
            'inss_employer_rate' => $inssData['employer_rate'],
            'inss_employer_amount' => $inssData['employer_amount'],
            'statutory_deductions_total' => $statutoryDeductionsTotal,
            'gross_pay' => $grossPay,
            'net_pay' => $netPay,
            'per_day_salary' => $perDaySalary,

            // Days
            'working_days' => $workingDaysCount,
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'half_day_deduction' => $halfDayDeduction,
            'absent_days' => $absentDays,
            'absent_day_deduction' => $absentDayDeduction,
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'unpaid_leave_deduction' => $unpaidLeaveDeduction,

            // OverTime
            'manual_overtime_hours' => $totalManualOvertimeHour,
            'total_manual_overtimes' => $totalManualOvertimes,
            'attendance_overtime_hours' => $overtimeHours,
            'attendance_overtime_rate' => $employee->rate_per_hour ?? 0,
            'attendance_overtime_amount' => $overtimeAmount,
            'overtime_hours' => $totalAllOverTimeHours,

            // Breakdown JSONs
            'allowances_breakdown' => $allowancesBreakdown,
            'deductions_breakdown' => $deductionsBreakdown,
            'statutory_deductions_breakdown' => $statutoryDeductionsBreakdown,
            'manual_overtimes_breakdown' => $manualOvertimesBreakdown,
            'loans_breakdown' => $loansBreakdown,
            'minimum_wage_required' => $minimumWageData['minimum_required'],
            'minimum_wage_compliant' => $minimumWageData['is_compliant'],
            'minimum_wage_gap' => $minimumWageData['gap'],
            'payroll_sector_code' => $minimumWageData['sector_code'],
            'status' => 'unpaid',
            'is_cancelled' => false,
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,

            'creator_id' => Auth::id(),
            'created_by' => creatorId(),
        ]);
    }

    private function activeEntriesQuery(Payroll $payroll)
    {
        return PayrollEntry::query()
            ->where('payroll_id', $payroll->id)
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')->orWhere('is_cancelled', false);
            });
    }

    private function computePayrollTotals($entries): array
    {
        $employeeCount = (int) $entries->count();
        $allPaid = $employeeCount > 0
            ? $entries->every(fn (PayrollEntry $entry): bool => ($entry->status ?? 'unpaid') === 'paid')
            : false;

        return [
            'all_paid' => $allPaid,
            'values' => [
                'total_gross_pay' => (float) $entries->sum('gross_pay'),
                'total_deductions' => (float) $entries->sum('total_deductions'),
                'total_net_pay' => (float) $entries->sum('net_pay'),
                'total_irps' => (float) $entries->sum('irps_amount'),
                'total_inss_employee' => (float) $entries->sum('inss_employee_amount'),
                'total_inss_employer' => (float) $entries->sum('inss_employer_amount'),
                'employee_count' => $employeeCount,
            ],
        ];
    }

    private function calculateAllowances($employee, $basicSalary)
    {
        $allowances = Allowance::query()
            ->active()
            ->where('employee_id', $employee->user_id)
            ->where('created_by', creatorId())
            ->get();
        $breakdown = [];
        $total = 0;

        foreach ($allowances as $allowance) {
            $allowanceType = $allowance->allowanceType;
            $name = $allowanceType->name ?? 'Allowance';

            if ($allowance && $allowance->type === 'percentage') {
                $amount = ($basicSalary * $allowance->amount) / 100;
            } else {
                $amount = $allowance->amount;
            }

            $breakdown[$name] = $amount;
            $total += $amount;
        }

        return ['breakdown' => $breakdown, 'total' => $total];
    }

    private function calculateDeductions($employee, $basicSalary)
    {
        $deductions = Deduction::query()
            ->active()
            ->where('employee_id', $employee->user_id)
            ->where('created_by', creatorId())
            ->get();
        $breakdown = [];
        $total = 0;

        foreach ($deductions as $deduction) {
            $deductionType = $deduction->deductionType;
            $name = $deductionType->name ?? 'Deduction';

            if ($deduction && $deduction->type === 'percentage') {
                $amount = ($basicSalary * $deduction->amount) / 100;
            } else {
                $amount = $deduction->amount;
            }

            $breakdown[$name] = $amount;
            $total += $amount;
        }

        return ['breakdown' => $breakdown, 'total' => $total];
    }

    private function calculateAttendance($employee, $startDate, $endDate)
    {
        $attendances = Attendance::where('employee_id', $employee->user_id)
            ->active()
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $presentDays = $attendances->where('status', 'present')->count();
        $halfDays = $attendances->where('status', 'half day')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $overtimeHours = $attendances->sum('overtime_hours') ?? 0;

        // Calculate overtime amount using employee overtime rate
        $attendanceOvertimeRate = $employee->rate_per_hour ?? 0;
        $overtimeAmount = $overtimeHours * $attendanceOvertimeRate;

        return [
            'present_days' => $presentDays,
            'half_days' => $halfDays,
            'absent_days' => $absentDays,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount
        ];
    }

    private function calculateLeave($employee, $startDate, $endDate)
    {
        $leaveApplications = LeaveApplication::with('leave_type')->where('employee_id', $employee->user_id)
            ->active()
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', values: [$startDate, $endDate]);
            })
            ->get();

        $paidLeaveDays = 0;
        $unpaidLeaveDays = 0;

        foreach ($leaveApplications as $leave) {
            $leaveDays = max(1, $leave->start_date->diffInDays($leave->end_date) + 1);
            if ($leave->leave_type && $leave->leave_type->is_paid) {
                $paidLeaveDays += $leaveDays;
            } else {
                $unpaidLeaveDays += $leaveDays;
            }
        }
        return [
            'paid_leave_days' => $paidLeaveDays,
            'unpaid_leave_days' => $unpaidLeaveDays
        ];
    }

    private function calculateOvertimes($employee, $basicSalary, $startDate, $endDate)
    {
        $overtimes = Overtime::query()
            ->active()
            ->where('employee_id', $employee->user_id)
            ->where('created_by', creatorId())
            ->where(function ($query): void {
                $query->where('approval_status', 'approved')
                    ->orWhere(function ($legacyQuery): void {
                        $legacyQuery->whereNull('approval_status')
                            ->where('status', 'active');
                    });
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->get();

        $breakdown = [];
        $total = 0;
        $totalManualOvertimeHour = 0;

        foreach ($overtimes as $overtime) {
            $name = $overtime->title ?? 'Manual Overtime';
            $amount = $overtime->hours * $overtime->rate;

            $breakdown[$name] = $amount;
            $total += $amount;
            $totalManualOvertimeHour += $overtime->hours;
        }

        return ['breakdown' => $breakdown, 'total' => $total, 'totalManualOvertimeHour' => $totalManualOvertimeHour];
    }

    private function calculateLoans($employee, $basicSalary, $startDate, $endDate)
    {
        $loans = Loan::with('loanType')->active()->where('employee_id', $employee->user_id)
            ->where('created_by', creatorId())
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->get();

        $breakdown = [];
        $total = 0;

        foreach ($loans as $loan) {
            $loanType = $loan->loanType;
            $name = $loanType->name ?? 'Loan';

            if ($loan->type === 'percentage') {
                $amount = ($basicSalary * $loan->amount) / 100;
            } else {
                $amount = $loan->amount;
            }

            $breakdown[$name] = $amount;
            $total += $amount;
        }

        return ['breakdown' => $breakdown, 'total' => $total];
    }

    public function destroyEntry(PayrollEntry $payrollEntry)
    {
        if (Auth::user()->can('delete-payslip')) {
            $payroll = Payroll::query()->find($payrollEntry->payroll_id);
            if (!$payroll || !$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }

            if ($payroll->status === 'cancelled') {
                return redirect()->back()->with('error', __('Cannot modify payslips from a cancelled payroll.'));
            }

            if ((bool) ($payrollEntry->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Payslip is already cancelled.'));
            }

            if (($payrollEntry->status ?? 'unpaid') === 'paid') {
                return redirect()->back()->with('error', __('Paid payslips cannot be cancelled.'));
            }

            $validated = request()->validate([
                'cancellation_reason' => 'required|string|min:5|max:1000',
            ]);

            DestroySalarySlip::dispatch($payrollEntry);
            $payrollEntry->update([
                'status' => 'unpaid',
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id(),
                'cancellation_reason' => trim((string) $validated['cancellation_reason']),
            ]);

            $entries = $this->activeEntriesQuery($payroll)->get();
            $totals = $this->computePayrollTotals($entries);
            $nextStatus = $totals['values']['employee_count'] > 0 ? $payroll->status : 'draft';
            $payroll->update([
                'status' => $nextStatus,
                'is_payroll_paid' => $totals['all_paid'] ? 'paid' : 'unpaid',
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
                ...$totals['values'],
            ]);

            return redirect()->back()->with('success', __('Payslip cancelled successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function printPayslip(PayrollEntry $payrollEntry)
    {
        if (Auth::user()->can('download-payslip')) {
            $payroll = $payrollEntry->payroll;
            if (!$payroll || !$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }

            $payrollEntry->load(['employee.user', 'employee.designation', 'payroll']);

            return Inertia::render('Hrm/Payrolls/payslip/Payslip', [
                'payrollEntry' => $payrollEntry,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function paySalary(Request $request, PayrollEntry $payrollEntry)
    {
        if (Auth::user()->can('pay-payslip')) {
            if ((bool) ($payrollEntry->is_cancelled ?? false)) {
                return redirect()->back()->with('error', __('Cancelled payslips cannot be paid.'));
            }

            $payroll = $payrollEntry->payroll;
            if (!$payroll || !$this->checkPayrollAccess($payroll)) {
                return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
            }

            if ($payroll->status === 'cancelled') {
                return redirect()->back()->with('error', __('Cannot pay salaries from a cancelled payroll.'));
            }

            try {
                PaySalary::dispatch($request, $payrollEntry);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', $th->getMessage());
            }
            $payrollEntry->update(['status' => 'paid']);

            // Check if all payroll entries are paid and update payroll status
            $unpaidEntries = $this->activeEntriesQuery($payroll)->where('status', '!=', 'paid')->count();
            if ($unpaidEntries === 0) {
                $payroll->update(['is_payroll_paid' => 'paid']);
            }

            return redirect()->back()->with('success', __('Payment status updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function exportMozambiqueMap(Payroll $payroll)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        if (!$this->checkPayrollAccess($payroll)) {
            return redirect()->route('hrm.payrolls.index')->with('error', __('Permission denied'));
        }

        $payroll->load(['payrollEntries.employee.user']);

        $filename = sprintf(
            'mozambique-payroll-map-%s-%s.csv',
            $payroll->id,
            now()->format('Ymd-His')
        );

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'Payroll ID',
            'Payroll Title',
            'Pay Date',
            'Employee',
            'Employee Email',
            'Basic Salary',
            'Gross Pay',
            'Taxable Income',
            'IRPS',
            'INSS Employee Rate',
            'INSS Employee Amount',
            'INSS Employer Rate',
            'INSS Employer Amount',
            'Statutory Deductions Total',
            'Other Deductions Total',
            'Loans Total',
            'Net Pay',
            'Minimum Wage Required',
            'Minimum Wage Compliant',
            'Minimum Wage Gap',
            'Sector Code',
            'Payslip Status',
        ]);

        foreach ($payroll->payrollEntries as $entry) {
            $employeeName = $entry->employee?->user?->name ?? '-';
            $employeeEmail = $entry->employee?->user?->email ?? '-';
            $otherDeductions = (float) $entry->total_deductions - (float) $entry->statutory_deductions_total;

            fputcsv($handle, [
                $payroll->id,
                $payroll->title,
                optional($payroll->pay_date)->format('Y-m-d'),
                $employeeName,
                $employeeEmail,
                (float) $entry->basic_salary,
                (float) $entry->gross_pay,
                (float) $entry->taxable_income,
                (float) $entry->irps_amount,
                (float) $entry->inss_employee_rate,
                (float) $entry->inss_employee_amount,
                (float) $entry->inss_employer_rate,
                (float) $entry->inss_employer_amount,
                (float) $entry->statutory_deductions_total,
                $otherDeductions,
                (float) $entry->total_loans,
                (float) $entry->net_pay,
                $entry->minimum_wage_required !== null ? (float) $entry->minimum_wage_required : '',
                $entry->minimum_wage_compliant ? 'YES' : 'NO',
                (float) $entry->minimum_wage_gap,
                $entry->payroll_sector_code,
                $entry->status,
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle) ?: '';
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }
}
