<?php

use Illuminate\Support\Facades\Route;
use Workdo\Payfast\Http\Controllers\PayfastController;
use Workdo\Payfast\Http\Controllers\PayfastSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Payfast'])->group(function () {
    Route::post('/payfast/settings', [PayfastSettingsController::class, 'update'])->name('payfast.settings.update');
});

Route::middleware(['web'])->group(function () {
    Route::prefix('payfast')->group(function () {
        Route::post('/plan/company/payment', [PayfastController::class, 'planPayWithPayfast'])->name('payment.payfast.store')->middleware(['auth']);
        Route::get('/plan/company/status/{order_id}', [PayfastController::class, 'planGetPayfastStatus'])->name('payment.payfast.status');
    });
});