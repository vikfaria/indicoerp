<?php

use Workdo\BudgetPlanner\Http\Controllers\BudgetPeriodController;
use Workdo\BudgetPlanner\Http\Controllers\BudgetController;
use Workdo\BudgetPlanner\Http\Controllers\BudgetAllocationController;
use Workdo\BudgetPlanner\Http\Controllers\BudgetMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:BudgetPlanner'])->group(function () {
    Route::prefix('budget-planner/budget-periods')->name('budget-planner.budget-periods.')->group(function () {
        Route::get('/', [BudgetPeriodController::class, 'index'])->name('index');
        Route::post('/', [BudgetPeriodController::class, 'store'])->name('store');
        Route::put('/{budget_period}', [BudgetPeriodController::class, 'update'])->name('update');
        Route::post('/{budget_period}/approve', [BudgetPeriodController::class, 'approve'])->name('approve');
        Route::post('/{budget_period}/active', [BudgetPeriodController::class, 'active'])->name('active');
        Route::post('/{budget_period}/close', [BudgetPeriodController::class, 'close'])->name('close');
        Route::delete('/{budget_period}', [BudgetPeriodController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('budget-planner/budgets')->name('budget-planner.budgets.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::post('/', [BudgetController::class, 'store'])->name('store');
        Route::put('/{budget}', [BudgetController::class, 'update'])->name('update');
        Route::post('/{budget}/approve', [BudgetController::class, 'approve'])->name('approve');
        Route::post('/{budget}/active', [BudgetController::class, 'active'])->name('active');
        Route::post('/{budget}/close', [BudgetController::class, 'close'])->name('close');
        Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('budget-planner/budget-allocations')->name('budget-planner.budget-allocations.')->group(function () {
        Route::get('/', [BudgetAllocationController::class, 'index'])->name('index');
        Route::post('/', [BudgetAllocationController::class, 'store'])->name('store');
        Route::put('/{budget_allocation}', [BudgetAllocationController::class, 'update'])->name('update');
        Route::delete('/{budget_allocation}', [BudgetAllocationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('budget-planner/budget-monitoring')->name('budget-planner.budget-monitorings.')->group(function () {
        Route::get('/', [BudgetMonitoringController::class, 'index'])->name('index');
    });
});
