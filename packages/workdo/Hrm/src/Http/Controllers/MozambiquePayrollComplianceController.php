<?php

namespace Workdo\Hrm\Http\Controllers;

use App\Services\MozambiqueLabourComplianceService;
use App\Models\MozInssRate;
use App\Models\MozIrpsBracket;
use App\Models\MozIrpsTable;
use App\Models\MozMinimumWage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MozambiquePayrollComplianceController extends Controller
{
    public function __construct(private readonly MozambiqueLabourComplianceService $labourComplianceService)
    {
    }

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

        return Inertia::render('Hrm/SystemSetup/MozambiquePayroll/Index', [
            'irpsTables' => $irpsTables,
            'inssRates' => $inssRates,
            'minimumWages' => $minimumWages,
            'labourPolicy' => $this->labourComplianceService->getPolicy($companyId),
        ]);
    }

    public function updateLabourPolicy(Request $request)
    {
        if (!Auth::user()->can('edit-payrolls')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'overtime_daily_limit_hours' => 'nullable|numeric|min:0.25|max:24',
            'overtime_monthly_limit_hours' => 'nullable|numeric|min:1|max:744',
            'overtime_yearly_limit_hours' => 'nullable|numeric|min:1|max:9999',
            'leave_min_notice_days' => 'required|integer|min:0|max:365',
            'leave_max_consecutive_days' => 'nullable|integer|min:1|max:366',
            'leave_count_non_working_days' => 'required|boolean',
            'leave_count_holidays' => 'required|boolean',
        ]);

        setSetting('mz_overtime_daily_limit_hours', $validated['overtime_daily_limit_hours'] ?? '');
        setSetting('mz_overtime_monthly_limit_hours', $validated['overtime_monthly_limit_hours'] ?? '');
        setSetting('mz_overtime_yearly_limit_hours', $validated['overtime_yearly_limit_hours'] ?? '');
        setSetting('mz_leave_min_notice_days', (string) $validated['leave_min_notice_days']);
        setSetting('mz_leave_max_consecutive_days', $validated['leave_max_consecutive_days'] ?? '');
        setSetting('mz_leave_count_non_working_days', $validated['leave_count_non_working_days'] ? '1' : '0');
        setSetting('mz_leave_count_holidays', $validated['leave_count_holidays'] ? '1' : '0');

        return back()->with('success', __('Mozambique labour policy updated successfully.'));
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
}
