<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\BankAccount;
use Workdo\Account\Http\Requests\StoreBankAccountRequest;
use Workdo\Account\Http\Requests\UpdateBankAccountRequest;
use Workdo\Account\Events\CreateBankAccount;
use Workdo\Account\Events\UpdateBankAccount;
use Workdo\Account\Events\DestroyBankAccount;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Models\ChartOfAccount;

class BankAccountController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-bank-accounts')){
            $bankaccounts = BankAccount::query()
                ->with(['gl_account'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-bank-accounts')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-bank-accounts')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('account_number'), function($q) {
                    $q->where(function($query) {
                    $query->where('account_number', 'like', '%' . request('account_number') . '%');
                    $query->orWhere('account_name', 'like', '%' . request('account_number') . '%');
                    $query->orWhere('bank_name', 'like', '%' . request('account_number') . '%');
                    });
                })
                ->when(request('bank_name'), fn($q) => $q->where('bank_name', 'like', '%' . request('bank_name') . '%'))
                ->when(request('account_type') !== null && request('account_type') !== '', fn($q) => $q->where('account_type', request('account_type')))
                ->when(request('is_active') !== null && request('is_active') !== '', fn($q) => $q->where('is_active', request('is_active') === '1'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Account/BankAccounts/Index', [
                'bankaccounts' => $bankaccounts,
                'chartofaccounts' => ChartOfAccount::where('created_by', creatorId())
                    ->whereBetween('account_code', ['1000', '1099'])
                    ->select('id', 'account_code', 'account_name')
                    ->orderBy('account_code')
                    ->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBankAccountRequest $request)
    {
        if(Auth::user()->can('create-bank-accounts')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', false);

            $bankaccount = new BankAccount();
            $bankaccount->account_number = $validated['account_number'];
            $bankaccount->account_name = $validated['account_name'];
            $bankaccount->bank_name = $validated['bank_name'];
            $bankaccount->branch_name = $validated['branch_name'];
            $bankaccount->account_type = $validated['account_type'];
//            $bankaccount->payment_gateway = $validated['payment_gateway'];
            $bankaccount->opening_balance = $validated['opening_balance'];
            $bankaccount->current_balance = $validated['current_balance'];
            $bankaccount->iban = $validated['iban'];
            $bankaccount->swift_code = $validated['swift_code'];
            $bankaccount->routing_number = $validated['routing_number'];
            $bankaccount->is_active = $validated['is_active'];
            $bankaccount->gl_account_id = $validated['gl_account_id'];
            $bankaccount->creator_id = Auth::id();
            $bankaccount->created_by = creatorId();
            $bankaccount->save();

            CreateBankAccount::dispatch($request, $bankaccount);

            return redirect()->route('account.bank-accounts.index')->with('success', __('The bank account has been created successfully.'));
        }
        else{
            return redirect()->route('account.bank-accounts.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankaccount)
    {
        if(Auth::user()->can('edit-bank-accounts')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', false);

            $bankaccount->account_number = $validated['account_number'];
            $bankaccount->account_name = $validated['account_name'];
            $bankaccount->bank_name = $validated['bank_name'];
            $bankaccount->branch_name = $validated['branch_name'];
            $bankaccount->account_type = $validated['account_type'];
//            $bankaccount->payment_gateway = $validated['payment_gateway'];
            $bankaccount->opening_balance = $validated['opening_balance'];
            $bankaccount->current_balance = $validated['current_balance'];
            $bankaccount->iban = $validated['iban'];
            $bankaccount->swift_code = $validated['swift_code'];
            $bankaccount->routing_number = $validated['routing_number'];
            $bankaccount->is_active = $validated['is_active'];
            $bankaccount->gl_account_id = $validated['gl_account_id'];
            $bankaccount->save();

            UpdateBankAccount::dispatch($request, $bankaccount);

            return redirect()->back()->with('success', __('The bank account details are updated successfully.'));
        }
        else{
            return redirect()->route('account.bank-accounts.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(BankAccount $bankaccount)
    {
        if(Auth::user()->can('delete-bank-accounts')){
            DestroyBankAccount::dispatch($bankaccount);
            $bankaccount->delete();

            return redirect()->back()->with('success', __('The bank account has been deleted.'));
        }
        else{
            return redirect()->route('account.bank-accounts.index')->with('error', __('Permission denied'));
        }
    }

    public function bankAccounts()
    {
        $bankAccounts = BankAccount::where('created_by', creatorId())
            ->where('is_active', true)
            ->select('id', 'account_name', 'account_number')
            ->get();

        return response()->json($bankAccounts);
    }
}
