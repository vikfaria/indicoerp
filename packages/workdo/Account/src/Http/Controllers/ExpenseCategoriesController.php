<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\ExpenseCategories;
use Workdo\Account\Http\Requests\StoreExpenseCategoriesRequest;
use Workdo\Account\Http\Requests\UpdateExpenseCategoriesRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Events\CreateExpenseCategories;
use Workdo\Account\Events\DestroyExpenseCategories;
use Workdo\Account\Events\UpdateExpenseCategories;
use Workdo\Account\Models\ChartOfAccount;

class ExpenseCategoriesController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-expense-categories')) {
            $expensecategories = ExpenseCategories::with('gl_account:id,account_name')
                ->select('id', 'category_name', 'category_code', 'gl_account_id', 'description', 'is_active', 'created_at')
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

            return Inertia::render('Account/SystemSetup/ExpenseCategories/Index', [
                'expensecategories' => $expensecategories,
                'chartofaccounts' => ChartOfAccount::where('created_by', creatorId())
                    ->whereBetween('account_code', ['5000', '5999'])
                    ->select('id', 'account_code', 'account_name')
                    ->orderBy('account_code')
                    ->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreExpenseCategoriesRequest $request)
    {
        if(Auth::user()->can('create-expense-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $expensecategories = new ExpenseCategories();
            $expensecategories->category_name = $validated['category_name'];
            $expensecategories->category_code = $validated['category_code'];
            $expensecategories->description = $validated['description'];
            $expensecategories->gl_account_id = $validated['gl_account_id'];
            $expensecategories->is_active = $validated['is_active'];
            $expensecategories->creator_id = Auth::id();
            $expensecategories->created_by = creatorId();
            $expensecategories->save();

            CreateExpenseCategories::dispatch($request, $expensecategories);

            return redirect()->route('account.expense-categories.index')->with('success', __('The expense categories has been created successfully.'));
        }
        else{
            return redirect()->route('account.expense-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateExpenseCategoriesRequest $request, ExpenseCategories $expensecategories)
    {
        if(Auth::user()->can('edit-expense-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $expensecategories->category_name = $validated['category_name'];
            $expensecategories->category_code = $validated['category_code'];
            $expensecategories->description = $validated['description'];
            $expensecategories->is_active = $validated['is_active'];
            $expensecategories->gl_account_id = $validated['gl_account_id'];
            $expensecategories->is_active = $validated['is_active'];
            $expensecategories->save();

            UpdateExpenseCategories::dispatch($request, $expensecategories);

            return redirect()->route('account.expense-categories.index')->with('success', __('The expense categories details are updated successfully.'));
        }
        else{
            return redirect()->route('account.expense-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(ExpenseCategories $expensecategories)
    {
        if(Auth::user()->can('delete-expense-categories')){

            DestroyExpenseCategories::dispatch($expensecategories);

            $expensecategories->delete();

            return redirect()->route('account.expense-categories.index')->with('success', __('The expense categories has been deleted.'));
        }
        else{
            return redirect()->route('account.expense-categories.index')->with('error', __('Permission denied'));
        }
    }
}
