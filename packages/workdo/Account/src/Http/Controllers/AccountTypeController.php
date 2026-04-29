<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\AccountType;
use Workdo\Account\Http\Requests\StoreAccountTypeRequest;
use Workdo\Account\Http\Requests\UpdateAccountTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Events\CreateAccountType;
use Workdo\Account\Events\DestroyAccountType;
use Workdo\Account\Events\UpdateAccountType;
use Workdo\Account\Models\AccountCategory;
use Workdo\Account\Models\ChartOfAccount;

class AccountTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-account-types')) {
            $accounttypes = AccountType::with(['category'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-account-types')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-account-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Account/SystemSetup/AccountTypes/Index', [
                'accounttypes' => $accounttypes,
                'accountcategories' => AccountCategory::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreAccountTypeRequest $request)
    {
        if (Auth::user()->can('create-account-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', false);

            $accounttype = new AccountType();
            $accounttype->category_id = $validated['category_id'];
            $accounttype->name = $validated['name'];
            $accounttype->code = $validated['code'];
            $accounttype->normal_balance = $validated['normal_balance'] === '1' ? 'credit' : 'debit';
            $accounttype->description = $validated['description'];
            $accounttype->is_active = $validated['is_active'];
            $accounttype->creator_id = Auth::id();
            $accounttype->created_by = creatorId();
            $accounttype->save();

            // Dispatch event for packages to handle their fields
            CreateAccountType::dispatch($request, $accounttype);

            return redirect()->route('account.account-types.index')->with('success', __('The account type has been created successfully.'));
        } else {
            return redirect()->route('account.account-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateAccountTypeRequest $request, AccountType $accounttype)
    {
        if (Auth::user()->can('edit-account-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', false);

            $accounttype->category_id = $validated['category_id'];
            $accounttype->name = $validated['name'];
            $accounttype->code = $validated['code'];
            $accounttype->normal_balance = $validated['normal_balance'] === '1' ? 'credit' : 'debit';
            $accounttype->description = $validated['description'];
            $accounttype->is_active = $validated['is_active'];
            $accounttype->save();

            // Dispatch event for packages to handle their fields
            UpdateAccountType::dispatch($request, $accounttype);

            return back()->with('success', __('The account type details are updated successfully.'));
        } else {
            return redirect()->route('account.account-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(AccountType $accounttype)
    {
        if (Auth::user()->can('delete-account-types')) {

            // Dispatch event for packages to handle their fields
            DestroyAccountType::dispatch($accounttype);

            $accounttype->delete();

            return redirect()->back()->with('success', __('The accounttype has been deleted.'));
        } else {
            return redirect()->route('account.account-types.index')->with('error', __('Permission denied'));
        }
    }
}
