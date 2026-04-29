<?php

use Illuminate\Support\Facades\Route;
use Workdo\Paystack\Http\Controllers\PaystackController;
use Workdo\Paystack\Http\Controllers\PaystackSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Paystack'])->group(function () {
    Route::post('/paystack/settings', [PaystackSettingsController::class, 'update'])->name('paystack.settings.update');
});

Route::middleware(['web'])->group(function() {
    Route::prefix('paystack')->group(function() {
        Route::post('/plan/company/payment', [PaystackController::class,'planPayWithPaystack'])->name('payment.paystack.store')->middleware(['auth']);
        Route::match(['GET', 'POST'], '/plan/company/status/{plan_id}', [PaystackController::class,'planGetPaystackStatus'])->name('payment.paystack.status')->middleware(['auth']);
    });
});