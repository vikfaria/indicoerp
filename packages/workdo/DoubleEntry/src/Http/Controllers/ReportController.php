<?php

namespace Workdo\DoubleEntry\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\DoubleEntry\Services\ReportService;
use Workdo\Account\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        if(Auth::user()->can('manage-double-entry-reports')){

            $currentYear = date('Y');
            $financialYear = [
                'year_start_date' => "$currentYear-01-01",
                'year_end_date' => "$currentYear-12-31",
            ];

            return Inertia::render('DoubleEntry/Reports/Index', [
                'financialYear' => $financialYear,
            ]);

        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function generalLedger(Request $request)
    {
        $accounts = ChartOfAccount::where('created_by', creatorId())
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $currentYear = date('Y');
        $financialYear = [
            'year_start_date' => "$currentYear-01-01",
            'year_end_date' => "$currentYear-12-31",
        ];

        $firstAccount = $accounts->first();
        $accountId = $request->account_id ?: ($firstAccount ? $firstAccount->id : null);

        $filters = [
            'account_id' => $accountId,
            'from_date' => $request->from_date ?: $financialYear['year_start_date'],
            'to_date' => $request->to_date ?: $financialYear['year_end_date'],
        ];

        $data = $accountId ? $this->reportService->getGeneralLedger($filters) : null;

        $selectedAccount = null;
        if ($accountId) {
            $selectedAccount = ChartOfAccount::find($accountId);
        }

        return response()->json([
            'data' => $data,
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'financialYear' => $financialYear,
        ]);
    }

    public function printGeneralLedger(Request $request)
    {
        $filters = [
            'account_id' => $request->account_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ];

        $data = $this->reportService->getGeneralLedger($filters);

        $selectedAccount = null;
        if ($request->account_id) {
            $selectedAccount = ChartOfAccount::find($request->account_id);
        }

        return Inertia::render('DoubleEntry/Reports/Print/GeneralLedger', [
            'data' => $data,
            'selectedAccount' => $selectedAccount,
            'filters' => $filters,
        ]);
    }

    public function accountStatement(Request $request)
    {
        $accounts = ChartOfAccount::where('created_by', creatorId())
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name']);

        $currentYear = date('Y');
        $financialYear = [
            'year_start_date' => "$currentYear-01-01",
            'year_end_date' => "$currentYear-12-31",
        ];

        $firstAccount = $accounts->first();
        $accountId = $request->account_id ?: ($firstAccount ? $firstAccount->id : null);

        $filters = [
            'account_id' => $accountId,
            'from_date' => $request->from_date ?: $financialYear['year_start_date'],
            'to_date' => $request->to_date ?: $financialYear['year_end_date'],
        ];

        $data = $accountId ? $this->reportService->getGeneralLedger($filters) : null;

        $selectedAccount = null;
        if ($accountId) {
            $selectedAccount = ChartOfAccount::find($accountId);
        }

        return response()->json([
            'data' => $data,
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'financialYear' => $financialYear,
        ]);
    }

    public function printAccountStatement(Request $request)
    {
        $filters = [
            'account_id' => $request->account_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ];

        $data = $this->reportService->getGeneralLedger($filters);

        $selectedAccount = null;
        if ($request->account_id) {
            $selectedAccount = ChartOfAccount::find($request->account_id);
        }

        return Inertia::render('DoubleEntry/Reports/Print/AccountStatement', [
            'data' => $data,
            'selectedAccount' => $selectedAccount,
            'filters' => $filters,
        ]);
    }

    public function journalEntry(Request $request)
    {
        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";

        $data = $this->reportService->getJournalEntries([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => $request->status,
        ]);

        return response()->json($data);
    }

    public function printJournalEntry(Request $request)
    {
        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";

        $data = $this->reportService->getJournalEntries([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => $request->status,
        ]);

        return Inertia::render('DoubleEntry/Reports/Print/JournalEntry', [
            'data' => $data,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'status' => $request->status,
            ],
        ]);
    }

    public function accountBalance(Request $request)
    {
        $currentYear = date('Y');
        $asOfDate = $request->as_of_date ?: "$currentYear-12-31";
        $accountType = $request->account_type;
        $showZeroBalances = $request->show_zero_balances === 'true';

        $data = $this->reportService->getAccountBalances([
            'as_of_date' => $asOfDate,
            'account_type' => $accountType,
            'show_zero_balances' => $showZeroBalances,
        ]);

        return response()->json($data);
    }

    public function printAccountBalance(Request $request)
    {
        $currentYear = date('Y');
        $asOfDate = $request->as_of_date ?: "$currentYear-12-31";
        $accountType = $request->account_type;
        $showZeroBalances = $request->show_zero_balances === 'true';

        $data = $this->reportService->getAccountBalances([
            'as_of_date' => $asOfDate,
            'account_type' => $accountType,
            'show_zero_balances' => $showZeroBalances,
        ]);

        return Inertia::render('DoubleEntry/Reports/Print/AccountBalance', [
            'data' => $data,
            'filters' => [
                'as_of_date' => $asOfDate,
                'account_type' => $accountType,
            ],
        ]);
    }

    public function cashFlow(Request $request)
    {
        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";

        $data = $this->reportService->getCashFlow([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        return response()->json($data);
    }

    public function printCashFlow(Request $request)
    {
        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";

        $data = $this->reportService->getCashFlow([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        return Inertia::render('DoubleEntry/Reports/Print/CashFlow', [
            'data' => $data,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    public function expenseReport(Request $request)
    {
        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";

        $data = $this->reportService->getExpenseReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        return response()->json($data);
    }

    public function printExpenseReport(Request $request)
    {
        $currentYear = date('Y');
        $fromDate = $request->from_date ?: "$currentYear-01-01";
        $toDate = $request->to_date ?: "$currentYear-12-31";

        $data = $this->reportService->getExpenseReport([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        return Inertia::render('DoubleEntry/Reports/Print/ExpenseReport', [
            'data' => $data,
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }
}
