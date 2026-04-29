<?php

namespace Workdo\DoubleEntry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\DoubleEntry\Services\TrialBalanceService;

class TrialBalanceController extends Controller
{
    protected $trialBalanceService;

    public function __construct(TrialBalanceService $trialBalanceService)
    {
        $this->trialBalanceService = $trialBalanceService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-trial-balance')){
            $currentYear = date('Y');
            $fromDate = $request->from_date ?: "$currentYear-01-01";
            $toDate = $request->to_date ?: "$currentYear-12-31";

            $trialBalance = $this->trialBalanceService->generateTrialBalance($fromDate, $toDate);

            return Inertia::render('DoubleEntry/TrialBalance/Index', [
                'trialBalance' => $trialBalance,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function print(Request $request)
    {
        if(Auth::user()->can('print-trial-balance')){
            $currentYear = date('Y');
            $fromDate = $request->from_date ?: "$currentYear-01-01";
            $toDate = $request->to_date ?: "$currentYear-12-31";

            $trialBalance = $this->trialBalanceService->generateTrialBalance($fromDate, $toDate);

            return Inertia::render('DoubleEntry/TrialBalance/Print', [
                'trialBalance' => $trialBalance,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
