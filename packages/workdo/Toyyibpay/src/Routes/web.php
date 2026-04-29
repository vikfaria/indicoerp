<?php

use Illuminate\Support\Facades\Route;
use Workdo\Toyyibpay\Http\Controllers\ToyyibpaySettingsController;
use Workdo\Toyyibpay\Http\Controllers\ToyyibpayController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Toyyibpay'])->group(function () {
    Route::post('/toyyibpay/settings', [ToyyibpaySettingsController::class, 'update'])->name('toyyibpay.settings.update');
});

Route::middleware(['web'])->prefix('toyyibpay')->group(function () {
    Route::post('/plan/company/payment', [ToyyibpayController::class, 'planPayWithToyyibpay'])->name('payment.toyyibpay.store')->middleware(['auth']);
    Route::get('/plan/company/status', [ToyyibpayController::class, 'planGetToyyibpayStatus'])->name('payment.toyyibpay.status')->middleware(['auth']);
});