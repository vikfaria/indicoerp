<?php

use Illuminate\Support\Facades\Route;
use Workdo\Contract\Http\Controllers\ContractController;
use Workdo\Contract\Http\Controllers\ContractTypeController;
use Workdo\Contract\Http\Controllers\ContractRenewalController;
use Workdo\Contract\Http\Controllers\ContractAttachmentController;
use Workdo\Contract\Http\Controllers\ContractCommentController;
use Workdo\Contract\Http\Controllers\ContractNoteController;
use Workdo\Contract\Http\Controllers\ContractSettingsController;
use Workdo\Contract\Http\Controllers\ContractSignatureController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Contract'])->group(function () {

    // Contract type routes
    Route::get('contract-types', [ContractTypeController::class, 'index'])->name('contract-types.index');
    Route::post('contract-types', [ContractTypeController::class, 'store'])->name('contract-types.store');
    Route::put('contract-types/{id}', [ContractTypeController::class, 'update'])->name('contract-types.update');
    Route::delete('contract-types/{id}', [ContractTypeController::class, 'destroy'])->name('contract-types.destroy');

Route::prefix('contract')->group(function () {
    // Contract routes
    Route::get('/', [ContractController::class, 'index'])->name('contract.index');
    Route::post('/', [ContractController::class, 'store'])->name('contract.store');
    Route::get('/{id}', [ContractController::class, 'show'])->name('contract.show');
    Route::put('/{id}', [ContractController::class, 'update'])->name('contract.update');
    Route::patch('/{id}/status', [ContractController::class, 'updateStatus'])->name('contract.update-status');
    Route::delete('/{id}', [ContractController::class, 'destroy'])->name('contract.destroy');

    Route::post('/{id}/duplicate', [ContractController::class, 'duplicate'])->name('contract.duplicate');
    Route::get('/{id}/preview', [ContractController::class, 'preview'])->name('contract.preview');
    Route::post('{contract}/signatures', [ContractSignatureController::class, 'store'])->name('contract-signatures.store');

    // Contract attachment routes
    Route::post('{contract}/attachments', [ContractAttachmentController::class, 'store'])->name('contract-attachments.store');
    Route::delete('attachments/{attachment}', [ContractAttachmentController::class, 'destroy'])->name('contract-attachments.destroy');

    // Contract comment routes
    Route::post('{contract}/comments', [ContractCommentController::class, 'store'])->name('contract-comments.store');
    Route::put('comments/{comment}', [ContractCommentController::class, 'update'])->name('contract-comments.update');
    Route::delete('comments/{comment}', [ContractCommentController::class, 'destroy'])->name('contract-comments.destroy');

    // Contract note routes
    Route::post('{contract}/notes', [ContractNoteController::class, 'store'])->name('contract-notes.store');
    Route::put('notes/{note}', [ContractNoteController::class, 'update'])->name('contract-notes.update');
    Route::delete('notes/{note}', [ContractNoteController::class, 'destroy'])->name('contract-notes.destroy');

    // Contract renewal routes
    Route::post('{contract}/renewals', [ContractRenewalController::class, 'store'])->name('contract-renewals.store');
    Route::put('renewals/{renewal}', [ContractRenewalController::class, 'update'])->name('contract-renewals.update');
    Route::delete('renewals/{renewal}', [ContractRenewalController::class, 'destroy'])->name('contract-renewals.destroy');

    // Contract settings routes
    Route::post('settings', [ContractSettingsController::class, 'store'])->name('contract-settings.store');
});
});
