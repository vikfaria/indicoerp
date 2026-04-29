<?php

use Workdo\Webhook\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified','PlanModuleCheck:Webhook'])->group(function () {
    Route::prefix('webhook')->name('webhook.')->group(function () {
        Route::get('/settings', [WebhookController::class, 'index'])->name('settings.index');
        Route::post('/settings/store', [WebhookController::class, 'store'])->name('settings.store');
        Route::put('/settings/update/{webhook}', [WebhookController::class, 'update'])->name('settings.update');
        Route::delete('/settings/destroy/{webhook}', [WebhookController::class, 'destroy'])->name('settings.destroy');
        Route::put('/settings/toggle/{webhook}', [WebhookController::class, 'toggle'])->name('settings.toggle');
    });
});