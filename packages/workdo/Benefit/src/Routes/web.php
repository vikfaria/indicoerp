<?php

use Illuminate\Support\Facades\Route;
use Workdo\Benefit\Http\Controllers\BenefitSettingsController;
use Workdo\Benefit\Http\Controllers\BenefitController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Benefit'])->group(function () {
    Route::post('/benefit/settings', [BenefitSettingsController::class, 'update'])->name('benefit.settings.update');
});

Route::middleware(['web'])->prefix('benefit')->group(function () {
    Route::post('/plan/company/payment', [BenefitController::class, 'planPayWithBenefit'])->name('payment.benefit.store')->middleware(['auth']);
    Route::get('/plan/company/status', [BenefitController::class, 'planGetBenefitStatus'])->name('payment.benefit.status');
});