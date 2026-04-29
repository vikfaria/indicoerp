<?php

use Illuminate\Support\Facades\Route;

use Workdo\Timesheet\Http\Controllers\TimesheetController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Timesheet'])->group(function () {
    Route::prefix('timesheet')->name('timesheet.')->group(function () {
        Route::get('/', [TimesheetController::class, 'index'])->name('index');
        Route::post('/', [TimesheetController::class, 'store'])->name('store');
        Route::put('/{timesheet}', [TimesheetController::class, 'update'])->name('update');
        Route::delete('/{timesheet}', [TimesheetController::class, 'destroy'])->name('destroy');
        
        // Attendance hours route
        Route::get('/attendance-hours', [TimesheetController::class, 'fetchAttendanceHours'])->name('attendance-hours');
    });
});