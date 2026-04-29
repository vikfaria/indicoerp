<?php

use Illuminate\Support\Facades\Route;
use Workdo\Stripe\Http\Controllers\DashboardController;
use Workdo\Stripe\Http\Controllers\StripeItemController;
use Workdo\Stripe\Http\Controllers\StripeSettingsController;
use Workdo\Stripe\Http\Controllers\StripeController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Stripe'])->group(function () {
    Route::post('/stripe/settings', [StripeSettingsController::class, 'update'])->name('stripe.settings.update');
});

Route::middleware(['web'])->group(function() {
    Route::prefix('stripe')->group(function() {
        Route::post('/plan/company/payment', [StripeController::class,'planPayWithStripe'])->name('payment.stripe.store')->middleware(['auth']);
        Route::get('/plan/company/status', [StripeController::class,'planGetStripeStatus'])->name('payment.stripe.status')->middleware(['auth']);

    });
});
