<?php

use Workdo\ZoomMeeting\Http\Controllers\ZoomMeetingController;

use Illuminate\Support\Facades\Route;
use Workdo\ZoomMeeting\Http\Controllers\ZoomMeetingSettingsController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:ZoomMeeting'])->group(function () {
    Route::post('/zoom-meeting/settings', [ZoomMeetingSettingsController::class, 'update'])->name('zoom-meeting.settings.update');

    Route::prefix('zoom-meetings')->name('zoommeeting.zoom-meetings.')->group(function () {
        Route::get('/', [ZoomMeetingController::class, 'index'])->name('index');
        Route::post('/', [ZoomMeetingController::class, 'store'])->name('store');

        Route::put('/{zoommeeting}', [ZoomMeetingController::class, 'update'])->name('update');
        Route::delete('/{zoommeeting}', [ZoomMeetingController::class, 'destroy'])->name('destroy');
        Route::patch('/{zoommeeting}/status', [ZoomMeetingController::class, 'updateStatus'])->name('update-status');
    });
});