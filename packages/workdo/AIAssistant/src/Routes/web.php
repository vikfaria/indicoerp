<?php

use Illuminate\Support\Facades\Route;
use Workdo\AIAssistant\Http\Controllers\AIAssistantSettingsController;
use Workdo\AIAssistant\Http\Controllers\AIGeneratorController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:AIAssistant'])->group(function () {
    Route::prefix('ai-assistant')->name('ai-assistant.')->group(function () {
        Route::get('/settings', [AIAssistantSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AIAssistantSettingsController::class, 'store'])->name('settings.store');
        Route::post('/generate', [AIGeneratorController::class, 'generate'])->name('generate');
    });
});
