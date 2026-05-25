<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScePermissionChecks;
use App\Services\FinancialStatementsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialReportController extends Controller
{
    use ScePermissionChecks;

    public function balanceSheet(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $asOfDate = $request->get('date', date('Y-m-d'));
        $service = app(FinancialStatementsService::class);
        $data = $service->generateBalanceSheet(creatorId(), $asOfDate);

        return Inertia::render('Reports/BalanceSheet', [
            'data' => $data,
            'asOfDate' => $asOfDate,
        ]);
    }

    public function incomeStatement(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $year = $request->get('year', date('Y'));
        $startDate = $request->get('start_date', "{$year}-01-01");
        $endDate = $request->get('end_date', "{$year}-12-31");

        $service = app(FinancialStatementsService::class);
        $data = $service->generateIncomeStatementByNature(creatorId(), $startDate, $endDate);

        return Inertia::render('Reports/IncomeStatement', [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function cashFlow(Request $request)
    {
        if (!$this->canViewSceSuite()) {
            abort(403, __('Permission denied'));
        }

        $year = $request->get('year', date('Y'));
        $startDate = $request->get('start_date', "{$year}-01-01");
        $endDate = $request->get('end_date', "{$year}-12-31");

        $service = app(FinancialStatementsService::class);
        $data = $service->generateCashFlowStatement(creatorId(), $startDate, $endDate);

        return Inertia::render('Reports/CashFlow', [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
