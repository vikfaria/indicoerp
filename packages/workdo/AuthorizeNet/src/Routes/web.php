<?php

use Illuminate\Support\Facades\Route;
use Workdo\AuthorizeNet\Http\Controllers\AuthorizeNetSettingsController;
use Workdo\AuthorizeNet\Http\Controllers\AuthorizeNetController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:AuthorizeNet'])->group(function () {
    Route::post('/authorizenet/settings', [AuthorizeNetSettingsController::class, 'update'])->name('authorizenet.settings.update');
});

Route::middleware(['web'])->prefix('authorizenet')->group(function () {
    Route::post('/process-payment', [AuthorizeNetSettingsController::class, 'processPayment'])->name('authorizenet.process.payment');

    Route::post('/plan/payment', [AuthorizeNetController::class, 'planPayWithAuthorizeNet'])->name('payment.authorizenet.store')->middleware('auth', 'verified');
    Route::get('/plan/status', [AuthorizeNetController::class, 'planGetAuthorizeNetStatus'])->name('payment.authorizenet.status')->middleware('auth', 'verified');

});
