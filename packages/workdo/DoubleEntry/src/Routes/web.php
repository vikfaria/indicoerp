<?php

use Illuminate\Support\Facades\Route;
use Workdo\DoubleEntry\Http\Controllers\BalanceSheetController;
use Workdo\DoubleEntry\Http\Controllers\LedgerSummaryController;
use Workdo\DoubleEntry\Http\Controllers\ProfitLossController;
use Workdo\DoubleEntry\Http\Controllers\TrialBalanceController;
use Workdo\DoubleEntry\Http\Controllers\ReportController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:DoubleEntry'])->group(function () {

    Route::prefix('double-entry/balance-sheets')->name('double-entry.balance-sheets.')->group(function () {
        Route::get('/', [BalanceSheetController::class, 'index'])->name('index');
        Route::get('/list', [BalanceSheetController::class, 'list'])->name('list');
        Route::get('/comparisons', [BalanceSheetController::class, 'comparisons'])->name('comparisons');
        Route::post('/', [BalanceSheetController::class, 'store'])->name('store');
        Route::get('/{id}', [BalanceSheetController::class, 'show'])->name('show');
        Route::get('/comparison/print', [BalanceSheetController::class, 'comparisonPrint'])->name('comparison.print');
        Route::get('/{id}/print', [BalanceSheetController::class, 'print'])->name('print');
        Route::post('/{id}/finalize', [BalanceSheetController::class, 'finalize'])->name('finalize');
        Route::post('/{id}/notes', [BalanceSheetController::class, 'addNote'])->name('add-note');
        Route::delete('/{balanceSheetId}/notes/{noteId}', [BalanceSheetController::class, 'deleteNote'])->name('delete-note');
        Route::post('/compare', [BalanceSheetController::class, 'compare'])->name('compare');
        Route::get('/comparison/{id}', [BalanceSheetController::class, 'showComparison'])->name('comparison');
        Route::post('/year-end-close', [BalanceSheetController::class, 'yearEndClose'])->name('year-end-close');
        Route::delete('/{id}', [BalanceSheetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('double-entry/ledger-summary')->name('double-entry.ledger-summary.')->group(function () {
        Route::get('/', [LedgerSummaryController::class, 'index'])->name('index');
        Route::get('/print', [LedgerSummaryController::class, 'print'])->name('print');
    });

    Route::prefix('double-entry/profit-loss')->name('double-entry.profit-loss.')->group(function () {
        Route::get('/', [ProfitLossController::class, 'index'])->name('index');
        Route::get('/print', [ProfitLossController::class, 'print'])->name('print');
    });

    Route::prefix('double-entry/trial-balance')->name('double-entry.trial-balance.')->group(function () {
        Route::get('/', [TrialBalanceController::class, 'index'])->name('index');
        Route::get('/print', [TrialBalanceController::class, 'print'])->name('print');
    });

    Route::prefix('double-entry/reports')->name('double-entry.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
        Route::get('/general-ledger/print', [ReportController::class, 'printGeneralLedger'])->name('general-ledger.print');
        Route::get('/account-statement', [ReportController::class, 'accountStatement'])->name('account-statement');
        Route::get('/account-statement/print', [ReportController::class, 'printAccountStatement'])->name('account-statement.print');
        Route::get('/journal-entry', [ReportController::class, 'journalEntry'])->name('journal-entry');
        Route::get('/journal-entry/print', [ReportController::class, 'printJournalEntry'])->name('journal-entry.print');
        Route::get('/account-balance', [ReportController::class, 'accountBalance'])->name('account-balance');
        Route::get('/account-balance/print', [ReportController::class, 'printAccountBalance'])->name('account-balance.print');
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/cash-flow/print', [ReportController::class, 'printCashFlow'])->name('cash-flow.print');
        Route::get('/expense-report', [ReportController::class, 'expenseReport'])->name('expense-report');
        Route::get('/expense-report/print', [ReportController::class, 'printExpenseReport'])->name('expense-report.print');
    });
});