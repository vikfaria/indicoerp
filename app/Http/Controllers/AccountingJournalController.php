<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Models\AccountingJournal;
use App\Models\AccountingPeriod;
use App\Models\MonthlyClosingChecklist;
use App\Services\MonthlyClosingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Workdo\Account\Models\ChartOfAccount;

class AccountingJournalController extends Controller
{
    use ScePermissionChecks;

    public function index(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $journals = AccountingJournal::where('company_id', creatorId())
            ->with(['defaultDebitAccount:id,account_code', 'defaultCreditAccount:id,account_code'])
            ->withCount('journalEntries')
            ->orderBy('code')
            ->get();

        $periods = AccountingPeriod::where('company_id', creatorId())
            ->orderByDesc('fiscal_year')
            ->orderBy('period_number')
            ->get();

        return Inertia::render('Accounting/Journals/Index', [
            'journals' => $journals->map(function (AccountingJournal $journal) {
                return [
                    'id' => $journal->id,
                    'name' => $journal->name,
                    'code' => $journal->code,
                    'prefix' => $journal->getPrefix(),
                    'type' => $journal->type,
                    'is_active' => $journal->is_active,
                    'current_sequence' => $journal->journal_entries_count,
                    'default_debit_account' => $journal->defaultDebitAccount?->account_code,
                    'default_credit_account' => $journal->defaultCreditAccount?->account_code,
                    'requires_attachment' => $journal->requires_attachment,
                ];
            }),
            'periods' => $periods,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'prefix' => 'required|string|max:5|alpha_num',
            'type' => 'required|string|in:cash,bank,sales,purchases,salaries,adjustments,opening,closing,fixed_assets,fiscal,general',
            'default_debit_account' => 'nullable|string|max:20',
            'default_credit_account' => 'nullable|string|max:20',
            'requires_attachment' => 'boolean',
        ]);

        $prefix = strtoupper(trim($validated['prefix']));

        $request->validate([
            'prefix' => [
                Rule::unique('accounting_journals', 'code')
                    ->where(fn ($q) => $q->where('company_id', creatorId())),
            ],
        ]);

        AccountingJournal::create([
            'company_id' => creatorId(),
            'code' => $prefix,
            'numbering_prefix' => $prefix,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'default_debit_account_id' => $this->resolveAccountId($validated['default_debit_account'] ?? null),
            'default_credit_account_id' => $this->resolveAccountId($validated['default_credit_account'] ?? null),
            'requires_attachment' => (bool) ($validated['requires_attachment'] ?? false),
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', __('Diário criado com sucesso.'));
    }

    public function update(Request $request, AccountingJournal $journal)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($journal->company_id !== creatorId()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|string|in:cash,bank,sales,purchases,salaries,adjustments,opening,closing,fixed_assets,fiscal,general',
            'default_debit_account' => 'nullable|string|max:20',
            'default_credit_account' => 'nullable|string|max:20',
            'requires_attachment' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $journal->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'default_debit_account_id' => $this->resolveAccountId($validated['default_debit_account'] ?? null),
            'default_credit_account_id' => $this->resolveAccountId($validated['default_credit_account'] ?? null),
            'requires_attachment' => (bool) ($validated['requires_attachment'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', __('Diário actualizado com sucesso.'));
    }

    public function destroy(AccountingJournal $journal)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($journal->company_id !== creatorId()) {
            abort(403);
        }

        $journal->delete();
        return back()->with('success', __('Diário eliminado com sucesso.'));
    }

    // Monthly Closing
    public function monthlyClosing(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $year = $request->get('year', date('Y'));
        $month = $request->get('month', (int) date('m'));

        $periods = AccountingPeriod::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->orderBy('period_number')
            ->get();

        $currentPeriod = $periods->firstWhere('period_number', $month);
        $checklists = $currentPeriod
            ? MonthlyClosingChecklist::where('company_id', creatorId())
                ->where('accounting_period_id', $currentPeriod->id)
                ->orderBy('id')
                ->get()
            : collect();

        return Inertia::render('Accounting/MonthlyClosing/Index', [
            'periods' => $periods,
            'checklists' => $checklists,
            'currentYear' => $year,
            'currentMonth' => $month,
        ]);
    }

    public function startClosing(Request $request)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'year' => 'required|string|size:4',
            'month' => 'required|integer|min:1|max:13',
        ]);

        $period = $this->resolvePeriod($validated['year'], (int) $validated['month']);
        if (!$period) {
            return back()->with('error', __('Período contabilístico não encontrado.'));
        }

        $service = app(MonthlyClosingService::class);
        try {
            $service->initiateClosing($period->id, creatorId());
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('Não foi possível iniciar o fecho.');
            return back()->with('error', $message);
        }

        return back()->with('success', __('Fecho mensal iniciado.'));
    }

    public function completeCheck(Request $request, MonthlyClosingChecklist $check)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        if ($check->company_id !== creatorId()) {
            abort(403);
        }

        $service = app(MonthlyClosingService::class);
        $service->completeCheckItem($check->id, $request->input('notes'));

        return back()->with('success', __('Item de verificação concluído.'));
    }

    public function finalizeClosing(Request $request)
    {
        if (!$this->canManageSceAccounting()) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'year' => 'required|string|size:4',
            'month' => 'required|integer|min:1|max:13',
        ]);

        $period = $this->resolvePeriod($validated['year'], (int) $validated['month']);
        if (!$period) {
            return back()->with('error', __('Período contabilístico não encontrado.'));
        }

        $service = app(MonthlyClosingService::class);
        try {
            $service->finalizePeriodClosing($period->id, creatorId());
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: __('Não foi possível fechar o período.');
            return back()->with('error', $message);
        }

        return back()->with('success', __('Período fechado com sucesso.'));
    }

    private function resolveAccountId(?string $accountCode): ?int
    {
        $accountCode = trim((string) $accountCode);
        if ($accountCode === '') {
            return null;
        }

        $accountId = ChartOfAccount::where('created_by', creatorId())
            ->where('account_code', $accountCode)
            ->value('id');

        if ($accountId === null) {
            throw ValidationException::withMessages([
                'account' => __('Conta contabilística ":code" não foi encontrada para esta empresa.', ['code' => $accountCode]),
            ]);
        }

        return (int) $accountId;
    }

    private function resolvePeriod(string $year, int $month): ?AccountingPeriod
    {
        return AccountingPeriod::where('company_id', creatorId())
            ->where('fiscal_year', $year)
            ->where('period_number', $month)
            ->first();
    }
}
