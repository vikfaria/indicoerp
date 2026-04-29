<?php

namespace Workdo\DoubleEntry\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\DoubleEntry\Services\ProfitLossService;

class ProfitLossController extends Controller
{
    protected $profitLossService;

    public function __construct(ProfitLossService $profitLossService)
    {
        $this->profitLossService = $profitLossService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-profit-loss')){
            $currentYear = date('Y');
            $fromDate = $request->from_date ?: "$currentYear-01-01";
            $toDate = $request->to_date ?: "$currentYear-12-31";

            $profitLoss = $this->profitLossService->generateProfitLoss($fromDate, $toDate);

            return Inertia::render('DoubleEntry/ProfitLoss/Index', [
                'profitLoss' => $profitLoss,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function print(Request $request)
    {
        if(Auth::user()->can('print-profit-loss')){
            $currentYear = date('Y');
            $fromDate = $request->from_date ?: "$currentYear-01-01";
            $toDate = $request->to_date ?: "$currentYear-12-31";

            $profitLoss = $this->profitLossService->generateProfitLoss($fromDate, $toDate);

            return Inertia::render('DoubleEntry/ProfitLoss/Print', [
                'profitLoss' => $profitLoss,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
