<?php

use Illuminate\Support\Facades\Route;
use Workdo\CinetPay\Http\Controllers\CinetPaySettingsController;
use Workdo\CinetPay\Http\Controllers\CinetPayController;

Route::middleware('web')->group(function () {
    

    // Plan Payment Routes
    Route::post('/payment/cinetpay', [CinetPayController::class, 'planPayWithCinetPay'])->name('payment.cinetpay.store')->middleware('auth');
    Route::get('/payment/cinetpay/status', [CinetPayController::class, 'planGetCinetPayStatus'])->name('payment.cinetpay.status')->middleware('auth');
});

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:CinetPay'])->group(function () {
    // Settings    
    Route::post('/settings/cinetpay', [CinetPaySettingsController::class, 'update'])->name('cinetpay.settings.update');
});

Route::middleware(['web', 'auth'])->prefix('cinetpay')->group(function () {
    Route::post('/payment', [CinetPayController::class, 'planPayWithCinetPay'])->name('payment.cinetpay.store');
    Route::get('/payment/status', [CinetPayController::class, 'planGetCinetPayStatus'])->name('payment.cinetpay.status');
});