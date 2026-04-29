<?php

use Illuminate\Support\Facades\Route;
use Workdo\Midtrans\Http\Controllers\MidtransSettingsController;
use Workdo\Midtrans\Http\Controllers\MidtransController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Midtrans'])->group(function () {
    Route::post('/midtrans/settings', [MidtransSettingsController::class, 'update'])->name('midtrans.settings.update');
});

Route::middleware(['web'])->group(function () {
    Route::prefix('midtrans')->group(function () {
        Route::post('/plan/company/payment', [MidtransController::class, 'planPayWithMidtrans'])->name('payment.midtrans.store')->middleware(['auth']);
        Route::get('/plan/company/status', [MidtransController::class, 'planGetMidtransStatus'])->name('payment.midtrans.status')->middleware(['auth']);
    });
});