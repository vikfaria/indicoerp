<?php

use Illuminate\Support\Facades\Route;
use Workdo\Telegram\Http\Controllers\TelegramSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Telegram'])->group(function () {
    Route::get('telegram/settings', [TelegramSettingsController::class, 'index'])->name('telegram.settings.index');
    Route::post('telegram/settings/store', [TelegramSettingsController::class, 'store'])->name('telegram.settings.store');
});