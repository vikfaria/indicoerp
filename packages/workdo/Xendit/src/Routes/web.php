<?php

use Illuminate\Support\Facades\Route;
use Workdo\Xendit\Http\Controllers\XenditSettingsController;
use Workdo\Xendit\Http\Controllers\XenditController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Xendit'])->group(function () {
    Route::post('/xendit/settings', [XenditSettingsController::class, 'update'])->name('xendit.settings.update');
});

Route::middleware(['web'])->prefix('xendit')->group(function () {
    Route::post('/plan/company/payment', [XenditController::class, 'planPayWithXendit'])->name('payment.xendit.store')->middleware(['auth']);
    Route::get('/plan/company/status', [XenditController::class, 'planGetXenditStatus'])->name('payment.xendit.status')->middleware(['auth']);
});
