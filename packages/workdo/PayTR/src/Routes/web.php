<?php

use Illuminate\Support\Facades\Route;
use Workdo\PayTR\Http\Controllers\PayTRSettingsController;
use Workdo\PayTR\Http\Controllers\PayTRController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:PayTR'])->group(function () {
    Route::post('/paytr/settings', [PayTRSettingsController::class, 'update'])->name('paytr.settings.update');
});

Route::middleware(['web'])->prefix('paytr')->group(function () {
    Route::post('/plan/payment', [PayTRController::class, 'planPayWithPayTR'])->name('payment.paytr.store')->middleware('auth', 'verified');
    Route::get('/plan/status', [PayTRController::class, 'planGetPayTRStatus'])->name('payment.paytr.status')->middleware('auth', 'verified');
});