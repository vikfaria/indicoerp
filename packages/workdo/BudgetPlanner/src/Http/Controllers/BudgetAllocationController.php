<?php

namespace Workdo\BudgetPlanner\Http\Controllers;

use Workdo\BudgetPlanner\Models\BudgetAllocation;
use Workdo\BudgetPlanner\Models\Budget;
use Workdo\BudgetPlanner\Http\Requests\StoreBudgetAllocationRequest;
use Workdo\BudgetPlanner\Http\Requests\UpdateBudgetAllocationRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\BudgetPlanner\Events\CreateBudgetAllocation;
use Workdo\BudgetPlanner\Events\DestroyBudgetAllocation;
use Workdo\BudgetPlanner\Events\UpdateBudgetAllocation;

class BudgetAllocationController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-budget-allocations')){
            $budgetAllocations = BudgetAllocation::query()
                ->with(['budget', 'account'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-budget-allocations')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-budget-allocations')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), function($q) {
                    $search = request('search');
                    $q->whereHas('budget', fn($query) => $query->where('budget_name', 'like', "%{$search}%"))
                      ->orWhereHas('account', fn($query) => $query->where('account_name', 'like', "%{$search}%"));
                })
                ->when(request('budget_id'), fn($q) => $q->where('budget_id', request('budget_id')))
                ->when(request('account_id'), fn($q) => $q->where('account_id', request('account_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $budgets = Budget::where('created_by', creatorId())
                ->whereIn('status', ['approved', 'active'])
                ->get();

            $accounts = ChartOfAccount::where('created_by', creatorId())
                ->whereBetween('account_code', ['5000', '5999'])
                ->select('id', 'account_code', 'account_name')
                ->orderBy('account_code')
                ->get();

            return Inertia::render('BudgetPlanner/BudgetAllocations/Index', [
                'budgetAllocations' => $budgetAllocations,
                'budgets' => $budgets,
                'accounts' => $accounts,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBudgetAllocationRequest $request)
    {
        if(Auth::user()->can('create-budget-allocations')){
            $validated = $request->validated();

            $budgetAllocation = new BudgetAllocation();
            $budgetAllocation->budget_id = $validated['budget_id'];
            $budgetAllocation->account_id = $validated['account_id'];
            $budgetAllocation->allocated_amount = $validated['allocated_amount'];
            $budgetAllocation->spent_amount = 0;
            $budgetAllocation->remaining_amount = $validated['allocated_amount'];
            $budgetAllocation->creator_id = Auth::id();
            $budgetAllocation->created_by = creatorId();
            $budgetAllocation->save();

            // Update budget total amount and status
            $budget = Budget::find($validated['budget_id']);
            $budget->total_budget_amount = $budget->allocations()->sum('allocated_amount');

            // Auto-approve budget if it has allocations and is still draft
            if ($budget->allocations()->count() > 0 && $budget->status === 'draft') {
                $budget->status = 'approved';
            }

            $budget->save();

            CreateBudgetAllocation::dispatch($request, $budgetAllocation);

            return redirect()->route('budget-planner.budget-allocations.index')->with('success', __('The budget allocation has been created successfully.'));
        }
        else{
            return redirect()->route('budget-planner.budget-allocations.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBudgetAllocationRequest $request, BudgetAllocation $budget_allocation)
    {
        if(Auth::user()->can('edit-budget-allocations')){
            $validated = $request->validated();

            $budget_allocation->budget_id = $validated['budget_id'];
            $budget_allocation->account_id = $validated['account_id'];
            $budget_allocation->allocated_amount = $validated['allocated_amount'];
            $budget_allocation->remaining_amount = $validated['allocated_amount'] - $budget_allocation->spent_amount;
            $budget_allocation->save();

            // Update budget total amount
            $budget = Budget::find($budget_allocation->budget_id);
            $budget->total_budget_amount = $budget->allocations()->sum('allocated_amount');
            $budget->save();

            UpdateBudgetAllocation::dispatch($request, $budget_allocation);

            return back()->with('success', __('The budget allocation details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(BudgetAllocation $budget_allocation)
    {
        if(Auth::user()->can('delete-budget-allocations')){
            $budgetId = $budget_allocation->budget_id;

            DestroyBudgetAllocation::dispatch($budget_allocation);

            $budget_allocation->delete();

            // Update budget total amount
            $budget = Budget::find($budgetId);
            $budget->total_budget_amount = $budget->allocations()->sum('allocated_amount');
            $budget->save();

            return back()->with('success', __('The budget allocation has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
