<?php

use Illuminate\Support\Facades\Route;
use Workdo\Paypal\Http\Controllers\PaypalController;
use Workdo\Paypal\Http\Controllers\PaypalSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Paypal'])->group(function () {
    Route::post('/paypal/settings', [PaypalSettingsController::class, 'update'])->name('paypal.settings.update');
});
Route::middleware(['web'])->group(function() {
    Route::prefix('paypal')->group(function() {
        Route::post('/plan/company/payment', [PaypalController::class,'planPayWithPaypal'])->name('payment.paypal.store')->middleware(['auth']);
        Route::get('/plan/company/status/{plan_id}', [PaypalController::class,'planGetPaypalStatus'])->name('payment.paypal.status')->middleware(['auth']);

    });
});
