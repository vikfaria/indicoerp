<?php

use Illuminate\Support\Facades\Route;
use Workdo\Aamarpay\Http\Controllers\AamarpaySettingsController;
use Workdo\Aamarpay\Http\Controllers\AamarpayController;

Route::prefix('aamarpay')->group(function () {
Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Aamarpay'])->group(function () {
        Route::post('settings', [AamarpaySettingsController::class, 'update'])->name('aamarpay.settings.update');
});

    Route::middleware(['web'])->group(function () {
        Route::post('/plan/company/payment', [AamarpayController::class, 'planPayWithAamarpay'])->name('payment.aamarpay.store')->middleware(['auth', 'verified']);
    });

    Route::any('/plan/company/status', [AamarpayController::class, 'planGetAamarpayStatus'])->name('payment.aamarpay.status');
});
