<?php

use Illuminate\Support\Facades\Route;
use Workdo\PayTab\Http\Controllers\PayTabSettingsController;
use Workdo\PayTab\Http\Controllers\PayTabController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:PayTab'])->group(function () {
    Route::post('/paytab/settings', [PayTabSettingsController::class, 'update'])->name('paytab.settings.update');
});

Route::middleware(['web'])->prefix('paytab')->group(function () {
    Route::post('/plan/payment', [PayTabController::class, 'planPayWithPayTab'])->name('payment.paytab.store')->middleware('auth', 'verified');
    Route::get('/plan/status', [PayTabController::class, 'planGetPayTabStatus'])->name('payment.paytab.status')->middleware('auth', 'verified');
});