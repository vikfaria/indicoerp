<?php

use Illuminate\Support\Facades\Route;
use Workdo\Quotation\Http\Controllers\QuotationController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Quotation'])->group(function () {
    Route::resource('quotations', QuotationController::class);
    Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
    Route::post('quotations/{quotation}/sent', [QuotationController::class, 'sent'])->name('quotations.sent');
    Route::post('quotations/{quotation}/approve', [QuotationController::class, 'approve'])->name('quotations.approve');
    Route::post('quotations/{quotation}/reject', [QuotationController::class, 'reject'])->name('quotations.reject');
    Route::post('quotations/{quotation}/convert-to-invoice', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert-to-invoice');
    Route::post('quotations/{quotation}/create-revision', [QuotationController::class, 'createRevision'])->name('quotations.create-revision');
    Route::post('quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');
    Route::get('sales-quotations/warehouse/products', [QuotationController::class, 'getWarehouseProducts'])->name('quotations.warehouse.products');

});