<?php

use Illuminate\Support\Facades\Route;
use Workdo\Fedapay\Http\Controllers\FedapayController;
use Workdo\Fedapay\Http\Controllers\FedapaySettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Fedapay'])->group(function () {
    Route::post('/fedapay/settings', [FedapaySettingsController::class, 'update'])->name('fedapay.settings.update');
});

Route::middleware(['web'])->prefix('fedapay')->group(function () {
    // Plan Payment Routes
    Route::post('/plan/company/payment', [FedapayController::class, 'planPayWithFedapay'])->name('payment.fedapay.store')->middleware(['auth']);
    Route::get('/plan/company/status/{plan_id}', [FedapayController::class, 'planGetFedapayStatus'])->name('payment.fedapay.status')->middleware(['auth']);
});