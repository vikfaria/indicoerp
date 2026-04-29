<?php

use Illuminate\Support\Facades\Route;
use Workdo\Performance\Http\Controllers\PerformanceIndicatorCategoryController;
use Workdo\Performance\Http\Controllers\PerformanceIndicatorController;
use Workdo\Performance\Http\Controllers\GoalTypeController;
use Workdo\Performance\Http\Controllers\EmployeeGoalController;
use Workdo\Performance\Http\Controllers\ReviewCycleController;
use Workdo\Performance\Http\Controllers\EmployeeReviewController;
use Workdo\Performance\Http\Controllers\DashboardController;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    
    // Performance Indicators
    Route::prefix('performance/indicators')->name('performance.indicators.')->group(function () {
        Route::get('/', [PerformanceIndicatorController::class, 'index'])->name('index');
        Route::post('/', [PerformanceIndicatorController::class, 'store'])->name('store');
        Route::put('/{indicator}', [PerformanceIndicatorController::class, 'update'])->name('update');
        Route::delete('/{indicator}', [PerformanceIndicatorController::class, 'destroy'])->name('destroy');
    });
    
    // Employee Goals
    Route::prefix('performance/employee-goals')->name('performance.employee-goals.')->group(function () {
        Route::get('/', [EmployeeGoalController::class, 'index'])->name('index');
        Route::post('/', [EmployeeGoalController::class, 'store'])->name('store');
        Route::put('/{employeeGoal}', [EmployeeGoalController::class, 'update'])->name('update');
        Route::delete('/{employeeGoal}', [EmployeeGoalController::class, 'destroy'])->name('destroy');
    });
    
    // Review Cycles
    Route::prefix('performance/review-cycles')->name('performance.review-cycles.')->group(function () {
        Route::get('/', [ReviewCycleController::class, 'index'])->name('index');
        Route::post('/', [ReviewCycleController::class, 'store'])->name('store');
        Route::get('/{reviewCycle}', [ReviewCycleController::class, 'show'])->name('show');
        Route::put('/{reviewCycle}', [ReviewCycleController::class, 'update'])->name('update');
        Route::delete('/{reviewCycle}', [ReviewCycleController::class, 'destroy'])->name('destroy');
    });
    
    // Employee Reviews
    Route::prefix('performance/employee-reviews')->name('performance.employee-reviews.')->group(function () {
        Route::get('/', [EmployeeReviewController::class, 'index'])->name('index');
        Route::post('/', [EmployeeReviewController::class, 'store'])->name('store');
        Route::get('/{employeeReview}/conduct', [EmployeeReviewController::class, 'conduct'])->name('conduct');
        Route::post('/{employeeReview}/conduct', [EmployeeReviewController::class, 'conductStore'])->name('conduct.store');
        Route::get('/{employeeReview}', [EmployeeReviewController::class, 'show'])->name('show');
        Route::put('/{employeeReview}', [EmployeeReviewController::class, 'update'])->name('update');
        Route::delete('/{employeeReview}', [EmployeeReviewController::class, 'destroy'])->name('destroy');
    });
    
    // Performance Indicator Categories
    Route::prefix('performance/indicator-categories')->name('performance.indicator-categories.')->group(function () {
        Route::get('/', [PerformanceIndicatorCategoryController::class, 'index'])->name('index');
        Route::post('/', [PerformanceIndicatorCategoryController::class, 'store'])->name('store');
        Route::put('/{indicatorCategory}', [PerformanceIndicatorCategoryController::class, 'update'])->name('update');
        Route::delete('/{indicatorCategory}', [PerformanceIndicatorCategoryController::class, 'destroy'])->name('destroy');
    });
    
    // Goal Types
    Route::prefix('performance/goal-types')->name('performance.goal-types.')->group(function () {
        Route::get('/', [GoalTypeController::class, 'index'])->name('index');
        Route::post('/', [GoalTypeController::class, 'store'])->name('store');
        Route::put('/{goalType}', [GoalTypeController::class, 'update'])->name('update');
        Route::delete('/{goalType}', [GoalTypeController::class, 'destroy'])->name('destroy');
    });
});