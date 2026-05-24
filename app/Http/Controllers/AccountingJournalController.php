<?php

namespace App\Http\Controllers;

use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use App\Models\MonthlyClosingChecklist;
use App\Services\MonthlyClosingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccountingJournalController extends Controller
{
    public function index(Request $request)
    {
        $journals = AccountingJournal::where('company_id', creatorId())
            ->orderBy('prefix')
            ->get();

        $periods = AccountingPeriod::where('company_id', creatorId())
            ->orderByDesc('fiscal_year')
            ->orderBy('period_number')
            ->get();

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $journals,
            'periods' => $periods,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'prefix' => 'required|string|max:5|alpha_num',
            'type' => 'required|string|in:cash,bank,sales,purchases,salaries,adjustments,opening,closing,fixed_assets,fiscal,general',
            'default_debit_account' => 'nullable|string|max:20',
            'default_credit_account' => 'nullable|string|max:20',
            'requires_attachment' => 'boolean',
        ]);

        $validated['company_id'] = creatorId();
        $validated['created_by'] = Auth::id();
        $validated['is_active'] = true;

        AccountingJournal::create($validated);

        return back()->with('success', __('Diário criado com sucesso.'));
    }

    public function update(Request $request, AccountingJournal $journal)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string',
            'default_debit_account' => 'nullable|string|max:20',
            'default_credit_account' => 'nullable|string|max:20',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $journal->update($validated);

        return back()->with('success', __('Diário actualizado com sucesso.'));
    }

    public function destroy(AccountingJournal $journal)
    {
        $journal->delete();
        return back()->with('success', __('Diário eliminado com sucesso.'));
    }

    // Monthly Closing
    public function monthlyClosing(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', (int) date('m'));

        $periods = AccountingPeriod::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->orderBy('period_number')
            ->get();

        $checklists = MonthlyClosingChecklist::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->where('period_number', $month)
            ->orderBy('check_order')
            ->get();

        return Inertia::render('Accounting/MonthlyClosing/Index', [
            'periods' => $periods,
            'checklists' => $checklists,
            'currentYear' => $year,
            'currentMonth' => $month,
        ]);
    }

    public function startClosing(Request $request)
    {
        $service = app(MonthlyClosingService::class);
        $result = $service->initiateClosing(
            creatorId(),
            $request->input('year', date('Y')),
            (int) $request->input('month', date('m'))
        );
        return back()->with('success', __('Fecho mensal iniciado.'));
    }

    public function completeCheck(Request $request, MonthlyClosingChecklist $check)
    {
        $check->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => Auth::id(),
            'notes' => $request->input('notes'),
        ]);
        return back()->with('success', __('Item de verificação concluído.'));
    }

    public function finalizeClosing(Request $request)
    {
        $service = app(MonthlyClosingService::class);
        $result = $service->finalizeClosing(
            creatorId(),
            $request->input('year'),
            (int) $request->input('month')
        );

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }
        return back()->with('success', __('Período fechado com sucesso.'));
    }
}
