<?php

namespace Workdo\DoubleEntry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\DoubleEntry\Services\LedgerSummaryService;
use Workdo\Account\Models\ChartOfAccount;

class LedgerSummaryController extends Controller
{
    protected $ledgerSummaryService;

    public function __construct(LedgerSummaryService $ledgerSummaryService)
    {
        $this->ledgerSummaryService = $ledgerSummaryService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-ledger-summary')){
            $entries = $this->ledgerSummaryService->getAllLedgerEntries(
                $request->from_date,
                $request->to_date,
                $request->account_id
            );

            $accounts = ChartOfAccount::query()
                ->where('created_by', creatorId())
                ->where('is_active', 1)
                ->orderBy('account_code', 'asc')
                ->select('id', 'account_code', 'account_name')
                ->get();

            return Inertia::render('DoubleEntry/LedgerSummary/Index', [
                'entries' => $entries,
                'accounts' => $accounts,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function print(Request $request)
    {
        if(Auth::user()->can('print-ledger-summary')){
            $entries = $this->ledgerSummaryService->getAllLedgerEntries(
                $request->from_date,
                $request->to_date,
                $request->account_id,
                false
            );

            $accounts = ChartOfAccount::query()
                ->where('created_by', creatorId())
                ->where('is_active', 1)
                ->orderBy('account_code', 'asc')
                ->select('id', 'account_code', 'account_name')
                ->get();

            $selectedAccount = null;
            if ($request->account_id) {
                $selectedAccount = $accounts->firstWhere('id', $request->account_id);
            }

            return Inertia::render('DoubleEntry/LedgerSummary/Print', [
                'entries' => $entries,
                'selectedAccount' => $selectedAccount,
                'filters' => [
                    'from_date' => $request->from_date,
                    'to_date' => $request->to_date,
                ],
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
