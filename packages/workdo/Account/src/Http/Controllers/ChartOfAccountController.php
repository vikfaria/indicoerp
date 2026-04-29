<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Http\Requests\StoreChartOfAccountRequest;
use Workdo\Account\Http\Requests\UpdateChartOfAccountRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Events\CreateChartOfAccount;
use Workdo\Account\Events\DestroyChartOfAccount;
use Workdo\Account\Events\UpdateChartOfAccount;
use Workdo\Account\Models\AccountType;
use Workdo\Account\Models\JournalEntryItem;

class ChartOfAccountController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-chart-of-accounts')){
            $chartofaccounts = ChartOfAccount::query()
                ->with(['account_type', 'parent_account'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-chart-of-accounts')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-chart-of-accounts')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('account_code'), function($q) {
                    $q->where(function($query) {
                    $query->where('account_code', 'like', '%' . request('account_code') . '%');
                    $query->orWhere('account_name', 'like', '%' . request('account_code') . '%');
                    });
                })
                ->when(request('account_type_id') && request('account_type_id') !== 'all', fn($q) => $q->where('account_type_id', request('account_type_id')))
                ->when(request('normal_balance') && request('normal_balance') !== 'all', fn($q) => $q->where('normal_balance', request('normal_balance')))
                ->when(request('is_active') !== null && request('is_active') !== 'all', fn($q) => $q->where('is_active', request('is_active') === '1'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Account/ChartOfAccounts/Index', [
                'chartofaccounts' => $chartofaccounts,
                'accounttypes' => AccountType::where('created_by', creatorId())->select('id', 'name')->get(),
                'parentaccounts' => ChartOfAccount::where('created_by', creatorId())->select('id', 'account_name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        if(Auth::user()->can('create-chart-of-accounts')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $chartofaccount = new ChartOfAccount();
            $chartofaccount->account_code = $validated['account_code'];
            $chartofaccount->account_name = $validated['account_name'];

            // Set level based on parent account selection
            if ($validated['parent_account_id'] && $validated['parent_account_id'] !== '0') {
                $chartofaccount->level = 2;
                $chartofaccount->parent_account_id = $validated['parent_account_id'];
            } else {
                $chartofaccount->level = 1;
                $chartofaccount->parent_account_id = null;
            }

            $chartofaccount->normal_balance = $validated['normal_balance'];
            $chartofaccount->opening_balance = $validated['opening_balance'];
            $chartofaccount->current_balance = $validated['current_balance'];
            $chartofaccount->is_active = $validated['is_active'];
            $chartofaccount->description = $validated['description'];
            $chartofaccount->account_type_id = $validated['account_type_id'];
            $chartofaccount->creator_id = Auth::id();
            $chartofaccount->created_by = creatorId();
            $chartofaccount->save();

            // Dispatch event for packages to handle their fields
            CreateChartOfAccount::dispatch($request, $chartofaccount);

            return redirect()->route('account.chart-of-accounts.index')->with('success', __('The chart of account has been created successfully.'));
        }
        else{
            return redirect()->route('account.chart-of-accounts.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateChartOfAccountRequest $request, ChartOfAccount $chartofaccount)
    {
        if(Auth::user()->can('edit-chart-of-accounts')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            // Don't update account_code if it's a system account
            if ($chartofaccount->is_system_account != 1) {
                $chartofaccount->account_code = $validated['account_code'];
            }
            // Don't update account_name if it's a system account
            if ($chartofaccount->is_system_account != 1) {
                $chartofaccount->account_name = $validated['account_name'];
            }
            // Set level based on parent account selection
            if ($validated['parent_account_id'] && $validated['parent_account_id'] !== '0') {
                $chartofaccount->level = 2;
                $chartofaccount->parent_account_id = $validated['parent_account_id'];
            } else {
                $chartofaccount->level = 1;
                $chartofaccount->parent_account_id = null;
            }

            $chartofaccount->normal_balance = $validated['normal_balance'];
            $chartofaccount->opening_balance = $validated['opening_balance'];
            $chartofaccount->current_balance = $validated['current_balance'];
            $chartofaccount->is_active = $validated['is_active'];
            $chartofaccount->description = $validated['description'];
            $chartofaccount->account_type_id = $validated['account_type_id'];
            $chartofaccount->save();

            // Dispatch event for packages to handle their fields
            UpdateChartOfAccount::dispatch($request, $chartofaccount);

            return redirect()->back()->with('success', __('The chart of account details are updated successfully.'));
        }
        else{
            return redirect()->route('account.chart-of-accounts.index')->with('error', __('Permission denied'));
        }
    }

    public function show(ChartOfAccount $chartofaccount)
    {
        if(Auth::user()->can('view-chart-of-accounts')){
            $history = JournalEntryItem::with(['journalEntry'])
                ->where('account_id', $chartofaccount->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // Calculate actual balance from journal entries
            $totalDebits = JournalEntryItem::where('account_id', $chartofaccount->id)->sum('debit_amount');
            $totalCredits = JournalEntryItem::where('account_id', $chartofaccount->id)->sum('credit_amount');
            
            $calculatedBalance = $chartofaccount->normal_balance === 'debit' 
                ? ($chartofaccount->opening_balance + $totalDebits - $totalCredits)
                : ($chartofaccount->opening_balance + $totalCredits - $totalDebits);

            return Inertia::render('Account/ChartOfAccounts/Show', [
                'chartofaccount' => $chartofaccount->load(['account_type', 'parent_account']),
                'history' => $history,
                'calculatedBalance' => $calculatedBalance,
                'totalDebits' => $totalDebits,
                'totalCredits' => $totalCredits,
            ]);
        }
        else{
            return redirect()->route('account.chart-of-accounts.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(ChartOfAccount $chartofaccount)
    {
        if(Auth::user()->can('delete-chart-of-accounts')){

            // Dispatch event for packages to handle their fields
            DestroyChartOfAccount::dispatch($chartofaccount);

            $chartofaccount->delete();

            return redirect()->back()->with('success', __('The chart of account has been deleted.'));
        }
        else{
            return redirect()->route('account.chart-of-accounts.index')->with('error', __('Permission denied'));
        }
    }
}
