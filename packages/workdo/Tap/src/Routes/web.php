<?php

use Illuminate\Support\Facades\Route;
use Workdo\Tap\Http\Controllers\TapSettingsController;
use Workdo\Tap\Http\Controllers\TapController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Tap'])->group(function () {
    Route::post('/tap/settings', [TapSettingsController::class, 'update'])->name('tap.settings.update');
});

Route::middleware(['web'])->group(function () {
    Route::prefix('tap')->group(function () {
        Route::post('/plan/company/payment', [TapController::class, 'planPayWithTap'])->name('payment.tap.store')->middleware(['auth']);
        Route::get('/plan/company/status', [TapController::class, 'planGetTapStatus'])->name('payment.tap.status')->middleware(['auth']);
    });
});
