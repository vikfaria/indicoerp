<?php

use Illuminate\Support\Facades\Route;
use Workdo\ProductService\Http\Controllers\DashboardController;
use Workdo\ProductService\Http\Controllers\ProductServiceItemController;
use Workdo\ProductService\Http\Controllers\SystemSetupController;
use Workdo\ProductService\Http\Controllers\CategoryController;
use Workdo\ProductService\Http\Controllers\TaxController;
use Workdo\ProductService\Http\Controllers\UnitController;
use Workdo\ProductService\Http\Controllers\WarehouseStockController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:ProductService'])->group(function () {

    Route::prefix('product-service/items')->name('product-service.items.')->group(function () {
        Route::get('/', [ProductServiceItemController::class, 'index'])->name('index');
        Route::get('/create', [ProductServiceItemController::class, 'create'])->name('create');
        Route::post('/', [ProductServiceItemController::class, 'store'])->name('store');
        Route::get('/{item}', [ProductServiceItemController::class, 'show'])->name('show');
        Route::get('/{item}/edit', [ProductServiceItemController::class, 'edit'])->name('edit');
        Route::put('/{item}', [ProductServiceItemController::class, 'update'])->name('update');
        Route::delete('/{item}', [ProductServiceItemController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('product-service/stock')->name('product-service.stock.')->group(function () {
        Route::get('/', [ProductServiceItemController::class, 'stockIndex'])->name('index');
        Route::post('/', [ProductServiceItemController::class, 'stockStore'])->name('store');
    });

    Route::prefix('product-service/item-categories')->name('product-service.item-categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::put('/{itemCategory}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{itemCategory}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('product-service/taxes')->name('product-service.taxes.')->group(function () {
        Route::get('/', [TaxController::class, 'index'])->name('index');
        Route::post('/', [TaxController::class, 'store'])->name('store');
        Route::put('/{tax}', [TaxController::class, 'update'])->name('update');
        Route::delete('/{tax}', [TaxController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('product-service/units')->name('product-service.units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
        Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('api/product-service')->name('api.product-service.')->group(function () {
        Route::get('/items', [ProductServiceItemController::class, 'apiIndex'])->name('items.index');
    });
});
