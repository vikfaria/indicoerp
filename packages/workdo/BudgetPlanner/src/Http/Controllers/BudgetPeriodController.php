<?php

namespace Workdo\BudgetPlanner\Http\Controllers;

use Workdo\BudgetPlanner\Models\BudgetPeriod;
use Workdo\BudgetPlanner\Http\Requests\StoreBudgetPeriodRequest;
use Workdo\BudgetPlanner\Http\Requests\UpdateBudgetPeriodRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\BudgetPlanner\Events\ActiveBudgetPeriod;
use Workdo\BudgetPlanner\Events\ApproveBudgetPeriod;
use Workdo\BudgetPlanner\Events\CloseBudgetPeriod;
use Workdo\BudgetPlanner\Events\CreateBudgetPeriod;
use Workdo\BudgetPlanner\Events\DestroyBudgetPeriod;
use Workdo\BudgetPlanner\Events\UpdateBudgetPeriod;

class BudgetPeriodController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-budget-periods')){
            $budgetperiods = BudgetPeriod::query()
                ->with(['approvedBy'])
            ->where(function($q) {
                    if(Auth::user()->can('manage-any-budget-periods')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-budget-periods')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('period_name'), function($q) {
                    $q->where(function($query) {
                    $query->where('period_name', 'like', '%' . request('period_name') . '%');
                    $query->orWhere('financial_year', 'like', '%' . request('period_name') . '%');
                    });
                })
                ->when(request('financial_year'), function($q) {
                    $q->where('financial_year', 'like', '%' . request('financial_year') . '%');
                })
                ->when(request('status') && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('date_from'), function($q) {
                    $q->where(function($query) {
                        $query->whereDate('start_date', '>=', request('date_from'))
                              ->orWhereDate('end_date', '>=', request('date_from'));
                    });
                })
                ->when(request('date_to'), function($q) {
                    $q->where(function($query) {
                        $query->whereDate('start_date', '<=', request('date_to'))
                              ->orWhereDate('end_date', '<=', request('date_to'));
                    });
                })
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('BudgetPlanner/BudgetPeriods/Index', [
                'budgetperiods' => $budgetperiods,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreBudgetPeriodRequest $request)
    {
        if(Auth::user()->can('create-budget-periods')){
            $validated = $request->validated();

            $budgetperiod = new BudgetPeriod();
            $budgetperiod->period_name = $validated['period_name'];
            $budgetperiod->financial_year = $validated['financial_year'];
            $budgetperiod->start_date = $validated['start_date'];
            $budgetperiod->end_date = $validated['end_date'];
            $budgetperiod->status = 'draft'; // Default status for new periods
            $budgetperiod->creator_id = Auth::id();
            $budgetperiod->created_by = creatorId();
            $budgetperiod->save();

            CreateBudgetPeriod::dispatch($request, $budgetperiod);


            return redirect()->route('budget-planner.budget-periods.index')->with('success', __('The budget period has been created successfully.'));
        }
        else{
            return redirect()->route('budget-planner.budget-periods.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBudgetPeriodRequest $request, BudgetPeriod $budget_period)
    {
        if(Auth::user()->can('edit-budget-periods')){
            $validated = $request->validated();

            $budget_period->period_name = $validated['period_name'];
            $budget_period->financial_year = $validated['financial_year'];
            $budget_period->start_date = $validated['start_date'];
            $budget_period->end_date = $validated['end_date'];
            $budget_period->status = $validated['status'];

            $budget_period->save();

            UpdateBudgetPeriod::dispatch($request, $budget_period);

            return back()->with('success', __('The budget period details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function approve(BudgetPeriod $budget_period)
    {
        if(Auth::user()->can('approve-budget-periods')){
            if ($budget_period->status !== 'draft') {
                return back()->with('error', __('Only draft budget periods can be approved.'));
            }

            $budget_period->update([
                'status' => 'approved',
                'approved_by' => Auth::id()
            ]);

            ApproveBudgetPeriod::dispatch($budget_period);

            return back()->with('success', __('Budget period approved successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function active(BudgetPeriod $budget_period)
    {
        if(Auth::user()->can('active-budget-periods')){
            if ($budget_period->status !== 'approved') {
                return back()->with('error', __('Only approved budget periods can be active.'));
            }

            $budget_period->update(['status' => 'active']);

            ActiveBudgetPeriod::dispatch($budget_period);

            return back()->with('success', __('Budget period active successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function close(BudgetPeriod $budget_period)
    {
        if(Auth::user()->can('close-budget-periods')){
            if ($budget_period->status !== 'active') {
                return back()->with('error', __('Only active budget periods can be closed.'));
            }

            // Close the period
            $budget_period->update(['status' => 'closed']);

            // Close all budgets in this period
            $budget_period->budgets()->update(['status' => 'closed']);

            CloseBudgetPeriod::dispatch($budget_period);

            return back()->with('success', __('Budget period and all associated budgets closed successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(BudgetPeriod $budget_period)
    {
        if(Auth::user()->can('delete-budget-periods')){

            DestroyBudgetPeriod::dispatch($budget_period);

            $budget_period->delete();

            return back()->with('success', __('The budget period has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }


}
