<?php

use Illuminate\Support\Facades\Route;
use Workdo\Cashfree\Http\Controllers\CashfreeSettingsController;
use Workdo\Cashfree\Http\Controllers\CashfreeController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Cashfree'])->group(function () {
    Route::post('/cashfree/settings', [CashfreeSettingsController::class, 'update'])->name('cashfree.settings.update');
});

Route::middleware(['web'])->group(function () {
    // Plan Payment Routes
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/payment/cashfree', [CashfreeController::class, 'planPayWithCashfree'])->name('payment.cashfree.store');
        Route::get('/payment/cashfree/status', [CashfreeController::class, 'planGetCashfreeStatus'])->name('payment.cashfree.status');
    });
});
