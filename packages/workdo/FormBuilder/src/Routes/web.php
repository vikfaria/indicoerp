<?php

use Illuminate\Support\Facades\Route;
use Workdo\FormBuilder\Http\Controllers\FormController;

use Workdo\FormBuilder\Http\Controllers\PublicFormController;
use Workdo\FormBuilder\Http\Middleware\FormBuilderSharedDataMiddleware;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:FormBuilder'])->group(function () {
    Route::prefix('form-builder')->name('formbuilder.')->group(function () {
        Route::get('forms', [FormController::class, 'index'])->name('forms.index');
        Route::get('forms/create', [FormController::class, 'create'])->name('forms.create');
        Route::post('forms', [FormController::class, 'store'])->name('forms.store');
        Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
        Route::post('forms/{form}', [FormController::class, 'update'])->name('forms.update');
        Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
        Route::get('forms/{form}/responses', [FormController::class, 'responses'])->name('forms.responses');
        Route::delete('forms/{form}/responses/{response}', [FormController::class, 'destroyResponse'])->name('forms.responses.destroy');
        Route::put('forms/{form}/fields', [FormController::class, 'updateFields'])->name('forms.fields.update');
        Route::delete('forms/{form}/fields/{field}', [FormController::class, 'destroyField'])->name('forms.fields.destroy');
        Route::get('forms/{form}/conversion', [FormController::class, 'conversion'])->name('forms.conversion');
        Route::post('forms/{form}/conversion', [FormController::class, 'updateConversion'])->name('forms.conversion.update');
        Route::get('forms/{form}/conversion-data', [FormController::class, 'getConversionData'])->name('forms.conversion.data');
    });
});

// Public form routes (no auth required)
Route::middleware(['web', FormBuilderSharedDataMiddleware::class])->group(function () {
    Route::get('/form-builder/{code}', [PublicFormController::class, 'show'])->name('formbuilder.public.form.show');
    Route::post('/form-builder/{code}', [PublicFormController::class, 'submit'])->name('formbuilder.public.form.submit');
});