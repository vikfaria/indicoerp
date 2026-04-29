<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\BankAccount;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Account\Services\BankTransactionsService;

class BankTransactionController extends Controller
{
    public function __construct(private readonly BankTransactionsService $bankTransactionsService)
    {
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-bank-transactions')){
            $query = BankTransaction::with(['bankAccount'])
                ->where('created_by', creatorId());
            // Apply filters
            if ($request->bank_account_id) {
                $query->where('bank_account_id', $request->bank_account_id);
            }
            if ($request->transaction_type) {
                $query->where('transaction_type', $request->transaction_type);
            }
            if ($request->search) {
                $query->where('reference_number', 'like', '%' . $request->search . '%')
                     ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            if ($sortField) {
                $query->orderBy($sortField, $sortDirection);
            }

            $transactions = $query->paginate($request->get('per_page', 10));
            $bankAccounts = BankAccount::where('is_active', true)->where('created_by', creatorId())->get();

            return Inertia::render('Account/BankTransactions/Index', [
                'transactions' => $transactions,
                'bankAccounts' => $bankAccounts,
                'filters' => $request->only(['bank_account_id', 'transaction_type', 'search'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function markReconciled($id)
    {
        if(Auth::user()->can('reconcile-bank-transactions')){
            $transaction = BankTransaction::where('id', $id)
                ->where('created_by', creatorId())
                ->first();

            if($transaction && $transaction->reconciliation_status === 'unreconciled') {
                $transaction->reconciliation_status = 'reconciled';
                $transaction->save();

                return back()->with('success', __('Transaction marked as reconciled'));
            }

            return back()->with('error', __('Transaction not found or already reconciled'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function importCsv(Request $request)
    {
        if (!Auth::user()->can('manage-bank-transactions')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {
            $result = $this->bankTransactionsService->importBankStatementCsv(
                $request->file('file')->getRealPath(),
                (int) $validated['bank_account_id'],
                (int) creatorId()
            );

            return back()->with(
                'success',
                __('Import completed: :created created, :duplicates duplicates, :errors errors.', [
                    'created' => $result['created'],
                    'duplicates' => $result['duplicates'],
                    'errors' => $result['errors'],
                ])
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function autoReconcile(Request $request)
    {
        if (!Auth::user()->can('reconcile-bank-transactions')) {
            return back()->with('error', __('Permission denied'));
        }

        $validated = $request->validate([
            'bank_account_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'tolerance' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'day_window' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        try {
            $result = $this->bankTransactionsService->autoReconcileImportedTransactions(
                (int) creatorId(),
                !empty($validated['bank_account_id']) ? (int) $validated['bank_account_id'] : null,
                $validated['from_date'] ?? null,
                $validated['to_date'] ?? null,
                isset($validated['tolerance']) ? (float) $validated['tolerance'] : 0.01,
                isset($validated['day_window']) ? (int) $validated['day_window'] : 3
            );

            return back()->with(
                'success',
                __('Reconciliation completed: :reconciled reconciled, :unmatched unmatched (from :processed processed).', [
                    'processed' => $result['processed'],
                    'reconciled' => $result['reconciled'],
                    'unmatched' => $result['unmatched'],
                ])
            );
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function downloadTemplate()
    {
        if (!Auth::user()->can('manage-bank-transactions')) {
            return back()->with('error', __('Permission denied'));
        }

        $rows = [
            ['transaction_date', 'transaction_type', 'reference_number', 'description', 'amount', 'running_balance', 'transaction_status'],
            ['2026-04-01', 'credit', 'CP-2026-04-001', 'Customer Payment Example', '5000.00', '15000.00', 'cleared'],
            ['2026-04-02', 'debit', 'VP-2026-04-003', 'Vendor Payment Example', '1250.50', '13749.50', 'cleared'],
        ];

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="bank-statement-template.csv"',
        ]);
    }
}
