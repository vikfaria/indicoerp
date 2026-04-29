<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\BankTransfer;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Http\Requests\StoreBankTransferRequest;
use Workdo\Account\Http\Requests\UpdateBankTransferRequest;
use Workdo\Account\Events\CreateBankTransfer;
use Workdo\Account\Events\UpdateBankTransfer;
use Workdo\Account\Events\DestroyBankTransfer;
use Workdo\Account\Events\ProcessBankTransfer;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BankTransferController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-bank-transfers')){
            $banktransfers = BankTransfer::query()
                ->with(['fromAccount', 'toAccount'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-bank-transfers')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-bank-transfers')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('transfer_number'), function($q) {
                    $q->where(function($query) {
                        $query->where('transfer_number', 'like', '%' . request('transfer_number') . '%')
                              ->orWhere('reference_number', 'like', '%' . request('transfer_number') . '%');
                    });
                })
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('from_account_id'), fn($q) => $q->where('from_account_id', request('from_account_id')))
                ->when(request('to_account_id'), fn($q) => $q->where('to_account_id', request('to_account_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $bankaccounts = BankAccount::where('is_active', true)->where('created_by', creatorId())->select('id', 'account_name', 'current_balance')->get();

            return Inertia::render('Account/BankTransfers/Index', [
                'banktransfers' => $banktransfers,
                'bankaccounts' => $bankaccounts,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBankTransferRequest $request)
    {
        if(Auth::user()->can('create-bank-transfers')){
            $validated = $request->validated();

            // Validate sufficient balance
            $fromAccount = BankAccount::find($validated['from_account_id']);
            $totalAmount = $validated['transfer_amount'] + ($validated['transfer_charges'] ?? 0);

            if ($fromAccount->current_balance < $totalAmount) {
                return back()->with('error', __('Insufficient balance in source account'));
            }

            $banktransfer = new BankTransfer();
            $banktransfer->transfer_number = BankTransfer::generateTransferNumber();
            $banktransfer->transfer_date = $validated['transfer_date'];
            $banktransfer->from_account_id = $validated['from_account_id'];
            $banktransfer->to_account_id = $validated['to_account_id'];
            $banktransfer->transfer_amount = $validated['transfer_amount'];
            $banktransfer->transfer_charges = $validated['transfer_charges'] ?? 0;
            $banktransfer->reference_number = $validated['reference_number'];
            $banktransfer->description = $validated['description'];
            $banktransfer->status = 'pending';
            $banktransfer->creator_id = Auth::id();
            $banktransfer->created_by = creatorId();
            $banktransfer->save();

            CreateBankTransfer::dispatch($request, $banktransfer);

            return redirect()->route('account.bank-transfers.index')->with('success', __('The bank transfer has been created successfully.'));
        }
        else{
            return redirect()->route('account.bank-transfers.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBankTransferRequest $request, BankTransfer $banktransfer)
    {
        if(Auth::user()->can('edit-bank-transfers')){
            if($banktransfer->status !== 'pending') {
                return back()->with('error', __('Only pending transfers can be edited'));
            }

            $validated = $request->validated();

            // Validate sufficient balance
            $fromAccount = BankAccount::find($validated['from_account_id']);
            $totalAmount = $validated['transfer_amount'] + ($validated['transfer_charges'] ?? 0);

            if ($fromAccount->current_balance < $totalAmount) {
                return back()->with('error', __('Insufficient balance in source account'));
            }

            $banktransfer->transfer_date = $validated['transfer_date'];
            $banktransfer->from_account_id = $validated['from_account_id'];
            $banktransfer->to_account_id = $validated['to_account_id'];
            $banktransfer->transfer_amount = $validated['transfer_amount'];
            $banktransfer->transfer_charges = $validated['transfer_charges'] ?? 0;
            $banktransfer->reference_number = $validated['reference_number'];
            $banktransfer->description = $validated['description'];
            $banktransfer->save();

            UpdateBankTransfer::dispatch($request, $banktransfer);

            return redirect()->back()->with('success', __('The bank transfer details are updated successfully.'));
        }
        else{
            return redirect()->route('account.bank-transfers.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(BankTransfer $banktransfer)
    {
        if(Auth::user()->can('delete-bank-transfers')){
            if($banktransfer->status !== 'pending') {
                return back()->with('error', __('Only pending transfers can be deleted'));
            }

            DestroyBankTransfer::dispatch($banktransfer);
            $banktransfer->delete();

            return redirect()->back()->with('success', __('The bank transfer has been deleted.'));
        }
        else{
            return redirect()->route('account.bank-transfers.index')->with('error', __('Permission denied'));
        }
    }

    public function process(BankTransfer $banktransfer)
    {
        if(Auth::user()->can('process-bank-transfers')){
            if($banktransfer->status !== 'pending') {
                return back()->with('error', __('Transfer is not in pending status'));
            }

            try {

                // Create dual bank transactions
                $bankTransactionsService = new BankTransactionsService();
                $bankTransactionsService->createTransferBankTransactions($banktransfer);

                // Create journal entries
                $journalService = new JournalService();
                $journalEntry = $journalService->createBankTransferJournal($banktransfer);

                // Update bank account balances
                $bankTransactionsService->updateBankBalance($banktransfer->from_account_id, -$banktransfer->total_debit);
                $bankTransactionsService->updateBankBalance($banktransfer->to_account_id, $banktransfer->transfer_amount);

                // Update status to completed
                $banktransfer->status = 'completed';
                $banktransfer->journal_entry_id = $journalEntry->id;
                $banktransfer->save();

                ProcessBankTransfer::dispatch($banktransfer);

                return back()->with('success', __('Bank transfer processed successfully'));
            } catch (\Exception $e) {
                // Update status to failed
                $banktransfer->status = 'failed';
                $banktransfer->save();

                return back()->with('error', __('Error processing transfer: ') . $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}