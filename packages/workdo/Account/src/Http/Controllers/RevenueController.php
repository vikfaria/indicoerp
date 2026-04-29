<?php

namespace Workdo\Account\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Events\ApproveRevenue;
use Workdo\Account\Events\CreateRevenue;
use Workdo\Account\Events\DestroyRevenue;
use Workdo\Account\Events\PostRevenue;
use Workdo\Account\Events\UpdateRevenue;
use Workdo\Account\Models\Revenue;
use Workdo\Account\Models\RevenueCategories;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Http\Requests\StoreRevenueRequest;
use Workdo\Account\Http\Requests\UpdateRevenueRequest;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Services\JournalService;

class RevenueController extends Controller
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
        if(Auth::user()->can('manage-revenues')){
            $query = Revenue::with(['category:id,category_name', 'bankAccount:id,account_name', 'chartOfAccount:id,account_code,account_name', 'approvedBy:id,name'])
                ->select('id', 'revenue_number', 'revenue_date', 'category_id', 'bank_account_id', 'chart_of_account_id', 'amount', 'description', 'reference_number', 'status', 'approved_by', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-revenues')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-revenues')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Apply filters
            if ($request->search) {
                $query->where('revenue_number', 'like', '%' . $request->search . '%');
            }
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->date_from && $request->date_to) {
                $query->whereBetween('revenue_date', [$request->date_from, $request->date_to]);
            }
            if ($request->bank_account_id) {
                $query->where('bank_account_id', $request->bank_account_id);
            }

            if ($request->sort) {
                $query->orderBy($request->sort, $request->direction ?? 'asc');
            } else {
                $query->latest();
            }

            $revenues = $query->paginate($request->per_page ?? 10)->withQueryString();

            $categories = RevenueCategories::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'category_name')
                ->get();

            $bankAccounts = BankAccount::where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'account_name')
                ->get();

            $chartOfAccounts = ChartOfAccount::where('created_by', creatorId())
                ->whereBetween('account_code', ['4000', '4999'])
                ->select('id', 'account_code', 'account_name')
                ->orderBy('account_code')
                ->get();
            return Inertia::render('Account/Revenues/Index', [
                'revenues' => $revenues,
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

    public function store(StoreRevenueRequest $request)
    {
        if(Auth::user()->can('create-revenues')){
            $validated = $request->validated();

            $revenue = new Revenue();
            $revenue->revenue_date = $validated['revenue_date'];
            $revenue->category_id = $validated['category_id'];
            $revenue->bank_account_id = $validated['bank_account_id'];
            $revenue->chart_of_account_id = $validated['chart_of_account_id'] ?? null;
            $revenue->amount = $validated['amount'];
            $revenue->description = $validated['description'];
            $revenue->reference_number = $validated['reference_number'];
            $revenue->status = 'draft';
            $revenue->creator_id = Auth::id();
            $revenue->created_by = creatorId();
            $revenue->save();

            CreateRevenue::dispatch($request, $revenue);

            return redirect()->route('account.revenues.index')->with('success', __('The revenue has been created successfully.'));
        }
        else{
            return redirect()->route('account.revenues.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateRevenueRequest $request, Revenue $revenue)
    {
        if(Auth::user()->can('edit-revenues') && $revenue->created_by == creatorId()){
            if ($revenue->status != 'draft') {
                return redirect()->route('account.revenues.index')->with('error', __('Cannot update posted revenue.'));
            }

            $validated = $request->validated();

            $revenue->revenue_date = $validated['revenue_date'];
            $revenue->category_id = $validated['category_id'];
            $revenue->bank_account_id = $validated['bank_account_id'];
            $revenue->chart_of_account_id = $validated['chart_of_account_id'] ?? null;
            $revenue->amount = $validated['amount'];
            $revenue->description = $validated['description'];
            $revenue->reference_number = $validated['reference_number'];
            $revenue->save();

            UpdateRevenue::dispatch($request, $revenue);

            return back()->with('success', __('The revenue details are updated successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(Revenue $revenue)
    {
        if(Auth::user()->can('delete-revenues') && $revenue->created_by == creatorId()){

            if ($revenue->status != 'draft') {
                return back()->with('error', __('Cannot delete posted revenue.'));
            }

            DestroyRevenue::dispatch($revenue);

            $revenue->delete();

            return back()->with('success', __('The revenue has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function approve(Revenue $revenue)
    {
        if(Auth::user()->can('approve-revenues') && $revenue->created_by == creatorId()){

            ApproveRevenue::dispatch($revenue);

            $revenue->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
            ]);

            return back()->with('success', __('Revenue approved successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function post(Revenue $revenue)
    {
        if(Auth::user()->can('post-revenues') && $revenue->created_by == creatorId()){
            try {
                $this->journalService->createRevenueEntryJournal($revenue);
                $this->bankTransactionsService->createRevenuePayment($revenue);

                PostRevenue::dispatch($revenue);

                $revenue->update([
                    'status' => 'posted',
                ]);

                return back()->with('success', __('Revenue posted successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
