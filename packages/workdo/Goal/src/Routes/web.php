<?php

use Workdo\Goal\Http\Controllers\GoalCategoryController;
use Workdo\Goal\Http\Controllers\GoalController;
use Workdo\Goal\Http\Controllers\GoalMilestoneController;
use Workdo\Goal\Http\Controllers\GoalContributionController;
use Workdo\Goal\Http\Controllers\GoalTrackingController;

use Illuminate\Support\Facades\Route;
use Workdo\Goal\Http\Controllers\DashboardController;
use Workdo\Goal\Http\Controllers\GoalItemController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Goal'])->group(function () {

    Route::prefix('goal/categories')->name('goal.categories.')->group(function () {
        Route::get('/', [GoalCategoryController::class, 'index'])->name('index');
        Route::post('/', [GoalCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [GoalCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [GoalCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('goal/goals')->name('goal.goals.')->group(function () {
        Route::get('/', [GoalController::class, 'index'])->name('index');
        Route::post('/', [GoalController::class, 'store'])->name('store');
        Route::get('/{goal}', [GoalController::class, 'show'])->name('show');
        Route::put('/{goal}', [GoalController::class, 'update'])->name('update');
        Route::delete('/{goal}', [GoalController::class, 'destroy'])->name('destroy');
        Route::post('/{goal}', [GoalController::class, 'active'])->name('active');
    });

    Route::prefix('goal/milestones')->name('goal.milestones.')->group(function () {
        Route::get('/', [GoalMilestoneController::class, 'index'])->name('index');
        Route::post('/', [GoalMilestoneController::class, 'store'])->name('store');
        Route::get('/{milestone}', [GoalMilestoneController::class, 'show'])->name('show');
        Route::put('/{milestone}', [GoalMilestoneController::class, 'update'])->name('update');
        Route::delete('/{milestone}', [GoalMilestoneController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('goal/contributions')->name('goal.contributions.')->group(function () {
        Route::get('/', [GoalContributionController::class, 'index'])->name('index');
        Route::post('/', [GoalContributionController::class, 'store'])->name('store');
        Route::put('/{contribution}', [GoalContributionController::class, 'update'])->name('update');
        Route::delete('/{contribution}', [GoalContributionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('goal/tracking')->name('goal.tracking.')->group(function () {
        Route::get('/', [GoalTrackingController::class, 'index'])->name('index');
        Route::post('/', [GoalTrackingController::class, 'store'])->name('store');
        Route::get('/{goalTracking}', [GoalTrackingController::class, 'show'])->name('show');
        Route::put('/{goalTracking}', [GoalTrackingController::class, 'update'])->name('update');
        Route::delete('/{goalTracking}', [GoalTrackingController::class, 'destroy'])->name('destroy');
    });

});
