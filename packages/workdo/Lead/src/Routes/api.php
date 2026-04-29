<?php

use Illuminate\Support\Facades\Route;
use Workdo\Lead\Http\Controllers\Api\DashboardApiController;
use Workdo\Lead\Http\Controllers\Api\LeadApiController;
use Workdo\Lead\Http\Controllers\Api\LeadStageApiController;
use Workdo\Lead\Http\Controllers\Api\PipelineApiController;

Route::prefix('api')->middleware(['api.json'])->group(function () {
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'lead'], function () {
        
        // Dashboard Routes
        Route::post('home', [DashboardApiController::class, 'index']);
        Route::post('chart-data', [DashboardApiController::class, 'chartData']);
        
        // Pipeline Routes
        Route::get('pipelines', [PipelineApiController::class, 'index']);
        Route::post('pipeline-create-update', [PipelineApiController::class, 'pipelineCreateAndUpdate']);
        
        // Lead Stage Routes
        Route::post('lead-stages', [LeadStageApiController::class, 'index']);
        Route::post('lead-stage-create-update', [LeadStageApiController::class, 'leadstageCreateAndUpdate']);
        
        // Lead Routes
        Route::post('leadboard', [LeadApiController::class, 'index']);
        Route::post('lead-create-update', [LeadApiController::class, 'leadCreateAndUpdate']);
        Route::post('lead-details', [LeadApiController::class, 'leadDetails']);
        Route::post('lead-delete', [LeadApiController::class, 'destroy']);
        Route::post('lead-stage-update', [LeadApiController::class, 'leadStageUpdate']);

        Route::get('get-users', [LeadApiController::class, 'getUsers']);
        
        // for get source & products
        Route::get('get-request-data', [LeadApiController::class, 'getRequestData']);        
    });
});