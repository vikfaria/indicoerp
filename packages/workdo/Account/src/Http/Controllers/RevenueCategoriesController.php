<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\RevenueCategories;
use Workdo\Account\Http\Requests\StoreRevenueCategoriesRequest;
use Workdo\Account\Http\Requests\UpdateRevenueCategoriesRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Events\CreateRevenueCategories;
use Workdo\Account\Events\DestroyRevenueCategories;
use Workdo\Account\Events\UpdateRevenueCategories;
use Workdo\Account\Models\ChartOfAccount;

class RevenueCategoriesController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-revenue-categories')) {
            $revenuecategories = RevenueCategories::with('gl_account:id,account_name')
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

            return Inertia::render('Account/SystemSetup/RevenueCategories/Index', [
                'revenuecategories' => $revenuecategories,
                'chartofaccounts' => ChartOfAccount::where('created_by', creatorId())
                    ->whereBetween('account_code', ['4000', '4999'])
                    ->select('id', 'account_code', 'account_name')
                    ->orderBy('account_code')
                    ->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreRevenueCategoriesRequest $request)
    {
        if(Auth::user()->can('create-revenue-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $revenuecategories = new RevenueCategories();
            $revenuecategories->category_name = $validated['category_name'];
            $revenuecategories->category_code = $validated['category_code'];
            $revenuecategories->description = $validated['description'];
            $revenuecategories->gl_account_id = $validated['gl_account_id'];
            $revenuecategories->is_active = $validated['is_active'];
            $revenuecategories->creator_id = Auth::id();
            $revenuecategories->created_by = creatorId();
            $revenuecategories->save();

            CreateRevenueCategories::dispatch($request, $revenuecategories);

            return redirect()->route('account.revenue-categories.index')->with('success', __('The revenue categories has been created successfully.'));
        }
        else{
            return redirect()->route('account.revenue-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateRevenueCategoriesRequest $request, RevenueCategories $revenuecategories)
    {
        if(Auth::user()->can('edit-revenue-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);


            $revenuecategories->category_name = $validated['category_name'];
            $revenuecategories->category_code = $validated['category_code'];
            $revenuecategories->description = $validated['description'];
            $revenuecategories->is_active = $validated['is_active'];
            $revenuecategories->gl_account_id = $validated['gl_account_id'];
            $revenuecategories->is_active = $validated['is_active'];
            $revenuecategories->save();

            UpdateRevenueCategories::dispatch($request, $revenuecategories);

            return redirect()->route('account.revenue-categories.index')->with('success', __('The revenue categories details are updated successfully.'));
        }
        else{
            return redirect()->route('account.revenue-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(RevenueCategories $revenuecategories)
    {
        if(Auth::user()->can('delete-revenue-categories')){

            DestroyRevenueCategories::dispatch($revenuecategories);

            $revenuecategories->delete();

            return redirect()->route('account.revenue-categories.index')->with('success', __('The revenue categories has been deleted.'));
        }
        else{
            return redirect()->route('account.revenue-categories.index')->with('error', __('Permission denied'));
        }
    }
}
