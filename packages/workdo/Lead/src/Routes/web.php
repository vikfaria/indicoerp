<?php

use Workdo\Lead\Http\Controllers\DealController;
use Workdo\Lead\Http\Controllers\DealTaskController;
use Workdo\Lead\Http\Controllers\LeadController;
use Workdo\Lead\Http\Controllers\LeadTaskController;
use Workdo\Lead\Http\Controllers\SourceController;
use Workdo\Lead\Http\Controllers\LabelController;
use Workdo\Lead\Http\Controllers\DealStageController;
use Workdo\Lead\Http\Controllers\LeadStageController;
use Workdo\Lead\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;
use Workdo\Lead\Http\Controllers\DashboardController;
use Workdo\Lead\Http\Controllers\ReportController;

Route::middleware(['web', 'auth', 'verified', 'PlanModuleCheck:Lead'])->group(function () {
    Route::get('/dashboard/crm', [DashboardController::class, 'index'])->name('lead.index');

    Route::resource('crm/pipelines', PipelineController::class)->names('lead.pipelines');

    Route::prefix('crm/lead-stages')->name('lead.lead-stages.')->group(function () {
        Route::get('/', [LeadStageController::class, 'index'])->name('index');
        Route::post('/', [LeadStageController::class, 'store'])->name('store');
        Route::post('/update-order', [LeadStageController::class, 'updateOrder'])->name('update-order');
        Route::put('/{leadstage}', [LeadStageController::class, 'update'])->name('update');
        Route::delete('/{leadstage}', [LeadStageController::class, 'destroy'])->name('destroy');       
    });

    Route::prefix('crm/deal-stages')->name('lead.deal-stages.')->group(function () {
        Route::get('/', [DealStageController::class, 'index'])->name('index');
        Route::post('/', [DealStageController::class, 'store'])->name('store');
        Route::post('/update-order', [DealStageController::class, 'updateOrder'])->name('update-order');
        Route::put('/{dealstage}', [DealStageController::class, 'update'])->name('update');
        Route::delete('/{dealstage}', [DealStageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('crm/labels')->name('lead.labels.')->group(function () {
        Route::get('/', [LabelController::class, 'index'])->name('index');
        Route::post('/', [LabelController::class, 'store'])->name('store');
        Route::put('/{label}', [LabelController::class, 'update'])->name('update');
        Route::delete('/{label}', [LabelController::class, 'destroy'])->name('destroy');       
    });

    Route::prefix('crm/sources')->name('lead.sources.')->group(function () {
        Route::get('/', [SourceController::class, 'index'])->name('index');
        Route::post('/', [SourceController::class, 'store'])->name('store');
        Route::put('/{source}', [SourceController::class, 'update'])->name('update');
        Route::delete('/{source}', [SourceController::class, 'destroy'])->name('destroy');        
    });

    Route::get('crm/leads/existing-clients', [LeadController::class, 'getExistingClients'])->name('lead.leads.existing-clients');
    Route::resource('crm/leads', LeadController::class)->names('lead.leads');
    Route::post('crm/leads/order', [LeadController::class, 'order'])->name('lead.leads.order');
    Route::patch('crm/leads/{lead}/labels', [LeadController::class, 'updateLabels'])->name('lead.leads.update-labels');
    Route::get('crm/leads/{lead}/available-users', [LeadController::class, 'getAvailableUsers'])->name('lead.leads.available-users');
    Route::post('crm/leads/{lead}/assign-users', [LeadController::class, 'assignUsers'])->name('lead.leads.assign-users');
    Route::delete('crm/leads/{lead}/users/{user}', [LeadController::class, 'removeUser'])->name('lead.leads.remove-user');
    Route::get('crm/leads/{lead}/available-products', [LeadController::class, 'getAvailableProducts'])->name('lead.leads.available-products');
    Route::post('crm/leads/{lead}/assign-products', [LeadController::class, 'assignProducts'])->name('lead.leads.assign-products');
    Route::delete('crm/leads/{lead}/products/{product}', [LeadController::class, 'removeProduct'])->name('lead.leads.remove-product');
    Route::get('crm/leads/{lead}/available-sources', [LeadController::class, 'getAvailableSources'])->name('lead.leads.available-sources');
    Route::post('crm/leads/{lead}/assign-sources', [LeadController::class, 'assignSources'])->name('lead.leads.assign-sources');
    Route::delete('crm/leads/{lead}/sources/{source}', [LeadController::class, 'removeSource'])->name('lead.leads.remove-source');
    Route::post('crm/leads/{lead}/emails', [LeadController::class, 'storeEmail'])->name('lead.leads.store-email');
    Route::post('crm/leads/{lead}/discussions', [LeadController::class, 'storeDiscussion'])->name('lead.leads.store-discussion');
    Route::post('crm/leads/{lead}/files', [LeadController::class, 'storeFile'])->name('lead.leads.store-file');
    Route::delete('crm/leads/{lead}/files/{file}', [LeadController::class, 'deleteFile'])->name('lead.leads.delete-file');
    Route::post('crm/leads/{lead}/convert-to-deal', [LeadController::class, 'convertToDeal'])->name('lead.leads.convert-to-deal');
    Route::post('crm/calls', [LeadController::class, 'callStore'])->name('lead.calls.store');
    Route::put('crm/calls/{call}', [LeadController::class, 'callUpdate'])->name('lead.calls.update');
    Route::delete('crm/calls/{call}', [LeadController::class, 'callDestroy'])->name('lead.calls.destroy');
    
    Route::resource('crm/tasks', LeadTaskController::class)->names('lead.tasks');
    
    Route::get('crm/stages/{pipeline}', [LeadController::class, 'getStagesByPipeline'])->name('lead.stages.by-pipeline');

    Route::resource('crm/deals', DealController::class)->names('lead.deals');
    Route::post('crm/deals/order', [DealController::class, 'order'])->name('lead.deals.order');
    Route::post('crm/deals/{deal}/change-status', [DealController::class, 'changeStatus'])->name('lead.deals.change-status');
    Route::patch('crm/deals/{deal}/labels', [DealController::class, 'updateLabels'])->name('lead.deals.update-labels');
    Route::post('crm/deals/{deal}/assign-users', [DealController::class, 'assignUsers'])->name('lead.deals.assign-users');
    Route::delete('crm/deals/{deal}/users/{user}', [DealController::class, 'removeUser'])->name('lead.deals.remove-user');
    Route::post('crm/deals/{deal}/assign-products', [DealController::class, 'assignProducts'])->name('lead.deals.assign-products');
    Route::delete('crm/deals/{deal}/products/{product}', [DealController::class, 'removeProduct'])->name('lead.deals.remove-product');
    Route::post('crm/deals/{deal}/assign-sources', [DealController::class, 'assignSources'])->name('lead.deals.assign-sources');
    Route::delete('crm/deals/{deal}/sources/{source}', [DealController::class, 'removeSource'])->name('lead.deals.remove-source');
    Route::post('crm/deals/{deal}/emails', [DealController::class, 'storeEmail'])->name('lead.deals.store-email');
    Route::post('crm/deals/{deal}/discussions', [DealController::class, 'storeDiscussion'])->name('lead.deals.store-discussion');
    Route::post('crm/deals/{deal}/files', [DealController::class, 'storeFile'])->name('lead.deals.store-file');
    Route::delete('crm/deals/{deal}/files/{file}', [DealController::class, 'deleteFile'])->name('lead.deals.delete-file');
    Route::post('crm/deals/{deal}/assign-clients', [DealController::class, 'assignClients'])->name('lead.deals.assign-clients');
    Route::delete('crm/deals/{deal}/clients/{client}', [DealController::class, 'removeClient'])->name('lead.deals.remove-client');
    Route::post('deal/calls', [DealController::class, 'callStore'])->name('deal.calls.store');
    Route::put('deal/calls/{call}', [DealController::class, 'callUpdate'])->name('deal.calls.update');
    Route::delete('deal/calls/{call}', [DealController::class, 'callDestroy'])->name('deal.calls.destroy');
    
    Route::resource('deal/tasks', DealTaskController::class)->names('deal.tasks');
    
    Route::prefix('crm/reports')->name('lead.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/leads', [ReportController::class, 'leadReports'])->name('leads');
        Route::get('/deals', [ReportController::class, 'dealReports'])->name('deals');
    });
});