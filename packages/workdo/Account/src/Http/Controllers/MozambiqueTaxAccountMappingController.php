<?php

namespace Workdo\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\MozTaxAccountMapping;

class MozambiqueTaxAccountMappingController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (!Auth::user()->can('manage-chart-of-accounts')) {
            return back()->with('error', __('Permission denied'));
        }

        $mappings = MozTaxAccountMapping::query()
            ->where('created_by', creatorId())
            ->with([
                'vatOutputAccount:id,account_code,account_name',
                'vatInputAccount:id,account_code,account_name',
                'withholdingPayableAccount:id,account_code,account_name',
                'withholdingReceivableAccount:id,account_code,account_name',
                'irpcExpenseAccount:id,account_code,account_name',
            ])
            ->latest('effective_from')
            ->latest('id')
            ->get();

        $chartAccounts = ChartOfAccount::query()
            ->where('created_by', creatorId())
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        return Inertia::render('Account/SystemSetup/MozambiqueTaxAccountMappings/Index', [
            'mappings' => $mappings,
            'chartAccounts' => $chartAccounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::user()->can('edit-chart-of-accounts')) {
            return back()->with('error', __('Permission denied'));
        }

        $request->merge([
            'vat_output_account_id' => $request->input('vat_output_account_id') ?: null,
            'vat_input_account_id' => $request->input('vat_input_account_id') ?: null,
            'withholding_payable_account_id' => $request->input('withholding_payable_account_id') ?: null,
            'withholding_receivable_account_id' => $request->input('withholding_receivable_account_id') ?: null,
            'irpc_expense_account_id' => $request->input('irpc_expense_account_id') ?: null,
            'effective_to' => $request->input('effective_to') ?: null,
            'notes' => $request->input('notes') ?: null,
        ]);

        $validated = $request->validate([
            'vat_output_account_id' => ['nullable', 'integer'],
            'vat_input_account_id' => ['nullable', 'integer'],
            'withholding_payable_account_id' => ['nullable', 'integer'],
            'withholding_receivable_account_id' => ['nullable', 'integer'],
            'irpc_expense_account_id' => ['nullable', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        foreach ([
            'vat_output_account_id',
            'vat_input_account_id',
            'withholding_payable_account_id',
            'withholding_receivable_account_id',
            'irpc_expense_account_id',
        ] as $field) {
            $validated[$field] = $this->resolveTenantAccountId($validated[$field] ?? null);
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['creator_id'] = Auth::id();
        $validated['created_by'] = creatorId();

        MozTaxAccountMapping::create($validated);

        return redirect()
            ->route('account.mozambique-tax-account-mappings.index')
            ->with('success', __('Mozambique tax account mapping has been saved successfully.'));
    }

    public function destroy(MozTaxAccountMapping $mapping): RedirectResponse
    {
        if (!Auth::user()->can('delete-chart-of-accounts')) {
            return back()->with('error', __('Permission denied'));
        }

        if ((int) $mapping->created_by !== (int) creatorId()) {
            abort(403);
        }

        $mapping->delete();

        return redirect()
            ->route('account.mozambique-tax-account-mappings.index')
            ->with('success', __('Mozambique tax account mapping deleted successfully.'));
    }

    private function resolveTenantAccountId(?int $accountId): ?int
    {
        if (!$accountId) {
            return null;
        }

        $account = ChartOfAccount::query()
            ->where('id', $accountId)
            ->where('created_by', creatorId())
            ->first();

        if (!$account) {
            abort(422, __('Invalid account selected.'));
        }

        return $account->id;
    }
}
