<?php

use Illuminate\Support\Facades\Route;
use Workdo\YooKassa\Http\Controllers\YooKassaSettingsController;
use Workdo\YooKassa\Http\Controllers\YooKassaController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:YooKassa'])->group(function () {
    Route::post('/yookassa/settings', [YooKassaSettingsController::class, 'update'])->name('yookassa.settings.update');
});

Route::middleware(['web'])->prefix('yookassa')->group(function () {
    Route::post('/plan/company/payment', [YooKassaController::class, 'planPayWithYooKassa'])->name('payment.yookassa.store')->middleware(['auth']);
    Route::get('/plan/company/status', [YooKassaController::class, 'planGetYooKassaStatus'])->name('payment.yookassa.status')->middleware(['auth']);
});