<?php

use Illuminate\Support\Facades\Route;
use Workdo\Mollie\Http\Controllers\MollieController;
use Workdo\Mollie\Http\Controllers\MollieSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Mollie'])->group(function () {
    Route::post('/mollie/settings', [MollieSettingsController::class, 'update'])->name('mollie.settings.update');
});

Route::middleware(['web'])->group(function () {
    Route::prefix('mollie')->group(function () {
        Route::post('/plan/company/payment', [MollieController::class, 'planPayWithMollie'])->name('payment.mollie.store')->middleware(['auth']);
        Route::get('/plan/company/status/{order_id}', [MollieController::class, 'planGetMollieStatus'])->name('payment.mollie.status');
    });
});