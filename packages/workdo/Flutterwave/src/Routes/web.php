<?php

use Illuminate\Support\Facades\Route;
use Workdo\Flutterwave\Http\Controllers\FlutterwaveSettingsController;
use Workdo\Flutterwave\Http\Controllers\FlutterwaveController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Flutterwave'])->group(function () {
    Route::post('/flutterwave/settings', [FlutterwaveSettingsController::class, 'update'])->name('flutterwave.settings.update');
});

Route::middleware(['web'])->prefix('flutterwave')->group(function () {
    Route::post('/plan/payment', [FlutterwaveController::class, 'planPayWithFlutterwave'])->name('payment.flutterwave.store')->middleware('auth', 'verified');
    Route::get('/plan/status/{order_id}', [FlutterwaveController::class, 'planGetFlutterwaveStatus'])->name('payment.flutterwave.status')->middleware('auth', 'verified');
    Route::get('/plan/cancel/{order_id}', [FlutterwaveController::class, 'planCancelFlutterwavePayment'])->name('payment.flutterwave.cancel')->middleware('auth', 'verified');
});