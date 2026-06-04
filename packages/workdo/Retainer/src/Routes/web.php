<?php

use Illuminate\Support\Facades\Route;
use Workdo\Retainer\Http\Controllers\RetainerController;
use Workdo\Retainer\Http\Controllers\RetainerPaymentController;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('retainers', [RetainerController::class, 'store'])->name('retainers.store');
    Route::post('retainers/{retainer}/sent', [RetainerController::class, 'sent'])->name('retainers.sent');
    Route::post('retainers/{retainer}/accept', [RetainerController::class, 'accept'])->name('retainers.accept');
    Route::post('retainers/{retainer}/reject', [RetainerController::class, 'reject'])->name('retainers.reject');
    Route::post('retainers/{retainer}/duplicate', [RetainerController::class, 'duplicate'])->name('retainers.duplicate');
    Route::post('retainers/{retainer}/convert-to-invoice', [RetainerController::class, 'convertToInvoice'])->name('retainers.convert-to-invoice');

    Route::post('retainer-payments', [RetainerPaymentController::class, 'store'])->name('retainer-payments.store');
    Route::patch('retainer-payments/{retainerPayment}/update-status', [RetainerPaymentController::class, 'updateStatus'])->name('retainer-payments.update-status');
});
