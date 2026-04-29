<?php

namespace Workdo\BudgetPlanner\Http\Controllers;

use Workdo\BudgetPlanner\Models\Budget;
use Workdo\BudgetPlanner\Models\BudgetPeriod;
use Workdo\BudgetPlanner\Http\Requests\StoreBudgetRequest;
use Workdo\BudgetPlanner\Http\Requests\UpdateBudgetRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\BudgetPlanner\Events\ActiveBudget;
use Workdo\BudgetPlanner\Events\ApproveBudget;
use Workdo\BudgetPlanner\Events\CloseBudget;
use Workdo\BudgetPlanner\Events\CreateBudget;
use Workdo\BudgetPlanner\Events\DestroyBudget;
use Workdo\BudgetPlanner\Events\UpdateBudget;

class BudgetController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-budgets')){
            $budgets = Budget::query()
                ->with(['budgetPeriod', 'approvedBy'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-budgets')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-budgets')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('budget_name'), function($q) {
                    $q->where('budget_name', 'like', '%' . request('budget_name') . '%');
                })
                ->when(request('budget_type') && request('budget_type') !== '', fn($q) => $q->where('budget_type', request('budget_type')))
                ->when(request('status') && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('period_id') && request('period_id') !== '', fn($q) => $q->where('period_id', request('period_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $budgetPeriods = BudgetPeriod::where('created_by', creatorId())
                ->where('status', 'active')
                ->get();

            return Inertia::render('BudgetPlanner/Budgets/Index', [
                'budgets' => $budgets,
                'budgetPeriods' => $budgetPeriods,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBudgetRequest $request)
    {
        if(Auth::user()->can('create-budgets')){
            $validated = $request->validated();

            $budget = new Budget();
            $budget->budget_name = $validated['budget_name'];
            $budget->period_id = $validated['period_id'];
            $budget->budget_type = $validated['budget_type'];
            $budget->total_budget_amount = 0;
            $budget->status = 'draft';
            $budget->creator_id = Auth::id();
            $budget->created_by = creatorId();
            $budget->save();

            CreateBudget::dispatch($request, $budget);

            return redirect()->route('budget-planner.budgets.index')->with('success', __('The budget has been created successfully.'));
        }
        else{
            return redirect()->route('budget-planner.budgets.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBudgetRequest $request, Budget $budget)
    {
        if(Auth::user()->can('edit-budgets')){
            if ($budget->status !== 'draft') {
                return back()->with('error', __('Only draft budgets can be edited.'));
            }

            $validated = $request->validated();

            $budget->budget_name = $validated['budget_name'];
            $budget->period_id = $validated['period_id'];
            $budget->budget_type = $validated['budget_type'];
            $budget->save();

            UpdateBudget::dispatch($request, $budget);

            return back()->with('success', __('The budget details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function approve(Budget $budget)
    {
        if(Auth::user()->can('approve-budgets')){
            if ($budget->status !== 'draft') {
                return back()->with('error', __('Only draft budgets can be approved.'));
            }

            $budget->update([
                'status' => 'approved',
                'approved_by' => Auth::id()
            ]);

            ApproveBudget::dispatch($budget);

            return back()->with('success', __('Budget approved successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function active(Budget $budget)
    {
        if(Auth::user()->can('active-budgets')){
            if ($budget->status !== 'approved') {
                return back()->with('error', __('Only approved budgets can be active.'));
            }

            $budget->update(['status' => 'active']);

            ActiveBudget::dispatch($budget);

            return back()->with('success', __('Budget active successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function close(Budget $budget)
    {
        if(Auth::user()->can('close-budgets')){
            if ($budget->status !== 'active') {
                return back()->with('error', __('Only active budgets can be closed.'));
            }

            $budget->update(['status' => 'closed']);

            CloseBudget::dispatch($budget);

            return back()->with('success', __('Budget closed successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Budget $budget)
    {
        if(Auth::user()->can('delete-budgets')){
            if ($budget->status !== 'draft') {
                return back()->with('error', __('Only draft budgets can be deleted.'));
            }

            DestroyBudget::dispatch($budget);

            $budget->delete();

            return redirect()->back()->with('success', __('The budget has been deleted.'));
        }
        else{
            return redirect()->route('budget-planner.budgets.index')->with('error', __('Permission denied'));
        }
    }
}
