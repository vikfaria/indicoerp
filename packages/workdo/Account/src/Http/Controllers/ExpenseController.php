<?php

namespace Workdo\Account\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Events\ApproveExpense;
use Workdo\Account\Events\CreateExpense;
use Workdo\Account\Events\DestroyExpense;
use Workdo\Account\Events\PostExpense;
use Workdo\Account\Events\UpdateExpense;
use Workdo\Account\Models\Expense;
use Workdo\Account\Models\ExpenseCategories;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Http\Requests\StoreExpenseRequest;
use Workdo\Account\Http\Requests\UpdateExpenseRequest;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;

class ExpenseController extends Controller
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-expenses')){
            $query = Expense::with(['category:id,category_name', 'bankAccount:id,account_name', 'chartOfAccount:id,account_code,account_name', 'approvedBy:id,name'])
                ->select('id', 'expense_number', 'expense_date', 'category_id', 'bank_account_id', 'chart_of_account_id', 'amount', 'description', 'reference_number', 'status', 'approved_by', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-expenses')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-expenses')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            if ($request->search) {
                $query->where('expense_number', 'like', '%' . $request->search . '%');
            }
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->date_from && $request->date_to) {
                $query->whereBetween('expense_date', [$request->date_from, $request->date_to]);
            }
            if ($request->bank_account_id) {
                $query->where('bank_account_id', $request->bank_account_id);
            }

            if ($request->sort) {
                $query->orderBy($request->sort, $request->direction ?? 'asc');
            } else {
                $query->latest();
            }

            $expenses = $query->paginate($request->per_page ?? 10)->withQueryString();

            $categories = ExpenseCategories::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'category_name')
                ->get();

            $bankAccounts = BankAccount::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'account_name')
                ->get();

            $chartOfAccounts = ChartOfAccount::where('created_by', creatorId())
                ->where('is_active', true)
                ->whereBetween('account_code', ['5000', '6999'])
                ->select('id', 'account_code', 'account_name')
                ->orderBy('account_code')
                ->get();

            return Inertia::render('Account/Expenses/Index', [
                'expenses' => $expenses,
                'categories' => $categories,
                'bankAccounts' => $bankAccounts,
                'chartOfAccounts' => $chartOfAccounts,
                'filters' => $request->only(['search', 'category_id', 'status', 'date_from', 'date_to', 'bank_account_id'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreExpenseRequest $request)
    {
        if(Auth::user()->can('create-expenses')){
            $validated = $request->validated();

            $expense = new Expense();
            $expense->expense_date = $validated['expense_date'];
            $expense->category_id = $validated['category_id'];
            $expense->bank_account_id = $validated['bank_account_id'];
            $expense->chart_of_account_id = $validated['chart_of_account_id'];
            $expense->amount = $validated['amount'];
            $expense->description = $validated['description'];
            $expense->reference_number = $validated['reference_number'];
            $expense->status = 'draft';
            $expense->creator_id = Auth::id();
            $expense->created_by = creatorId();
            $expense->save();

            CreateExpense::dispatch($request, $expense);

            return redirect()->route('account.expenses.index')->with('success', __('The expense has been created successfully.'));
        }
        else{
            return redirect()->route('account.expenses.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        if(Auth::user()->can('edit-expenses') && $expense->created_by == creatorId()){
            if ($expense->status != 'draft') {
                return redirect()->route('account.expenses.index')->with('error', __('Cannot update posted expense.'));
            }

            $validated = $request->validated();

            $expense->expense_date = $validated['expense_date'];
            $expense->category_id = $validated['category_id'];
            $expense->bank_account_id = $validated['bank_account_id'];
            $expense->chart_of_account_id = $validated['chart_of_account_id'];
            $expense->amount = $validated['amount'];
            $expense->description = $validated['description'];
            $expense->reference_number = $validated['reference_number'];
            $expense->save();

            UpdateExpense::dispatch($request, $expense);

            return back()->with('success', __('The expense details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Expense $expense)
    {
        if(Auth::user()->can('delete-expenses') && $expense->created_by == creatorId()){

            if ($expense->status != 'draft') {
                return back()->with('error', __('Cannot delete posted expense.'));
            }

            DestroyExpense::dispatch($expense);

            $expense->delete();

            return back()->with('success', __('Expense deleted successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function approve(Expense $expense)
    {
        if(Auth::user()->can('approve-expenses') && $expense->created_by == creatorId()){

            ApproveExpense::dispatch($expense);

            $expense->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
            ]);

            return back()->with('success', __('Expense approved successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function post(Expense $expense)
    {
        if(Auth::user()->can('post-expenses') && $expense->created_by == creatorId()){
            try {
                $this->journalService->createExpenseEntryJournal($expense);
                $this->bankTransactionsService->createExpensePayment($expense);

                PostExpense::dispatch($expense);

                $expense->update([
                    'status' => 'posted',
                ]);

                return back()->with('success', __('Expense posted successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
