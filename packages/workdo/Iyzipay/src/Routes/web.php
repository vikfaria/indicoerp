<?php

use Illuminate\Support\Facades\Route;
use Workdo\Iyzipay\Http\Controllers\IyzipayController;
use Workdo\Iyzipay\Http\Controllers\IyzipaySettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Iyzipay'])->group(function () {
    Route::post('/iyzipay/settings', [IyzipaySettingsController::class, 'update'])->name('iyzipay.settings.update');
});

Route::middleware(['web'])->group(function() {
    Route::prefix('iyzipay')->group(function() {
        Route::post('/plan/company/payment', [IyzipayController::class,'planPayWithIyzipay'])->name('payment.iyzipay.store')->middleware(['auth']);
        Route::match(['GET', 'POST'], '/plan/company/status/{plan_id}', [IyzipayController::class,'planGetIyzipayStatus'])->name('payment.iyzipay.status');
    });
});