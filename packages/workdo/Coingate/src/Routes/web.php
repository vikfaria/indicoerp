<?php

use Illuminate\Support\Facades\Route;
use Workdo\Coingate\Http\Controllers\CoingateSettingsController;
use Workdo\Coingate\Http\Controllers\CoingateController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Coingate'])->prefix('coingate')->group(function () {
    Route::post('/settings', [CoingateSettingsController::class, 'update'])->name('coingate.settings.update');
});

Route::middleware(['web'])->prefix('coingate')->group(function () {
    Route::post('/plan/payment/coingate', [CoingateController::class, 'planPayWithCoingate'])->name('plan.payment.coingate.store')->middleware(['auth', 'verified']);
    Route::get('/plan/payment/coingate/status', [CoingateController::class, 'planGetCoingateStatus'])->name('plan.payment.coingate.status')->middleware(['auth', 'verified']);
});
