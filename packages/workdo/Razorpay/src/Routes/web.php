<?php

use Illuminate\Support\Facades\Route;
use Workdo\Razorpay\Http\Controllers\RazorpaySettingsController;
use Workdo\Razorpay\Http\Controllers\RazorpayController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Razorpay'])->group(function () {
    Route::post('/razorpay/settings', [RazorpaySettingsController::class, 'update'])->name('razorpay.settings.update');
});

Route::middleware(['web'])->group(function() {
    Route::prefix('razorpay')->group(function() {
        Route::post('/plan/company/payment', [RazorpayController::class,'planPayWithRazorpay'])->name('payment.razorpay.store')->middleware(['auth']);
        Route::get('/plan/company/status', [RazorpayController::class,'planGetRazorpayStatus'])->name('payment.razorpay.status')->middleware(['auth']);
    });
});