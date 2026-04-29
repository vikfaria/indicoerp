<?php

use Illuminate\Support\Facades\Route;
use Workdo\Taskly\Http\Controllers\Api\DashboardApiController;
use Workdo\Taskly\Http\Controllers\Api\ProjectApiController;
use Workdo\Taskly\Http\Controllers\Api\TaskApiController;

Route::prefix('api')->middleware(['api.json'])->group(function () {
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'taskly'], function () {
        Route::get('users', [ProjectApiController::class, 'getUsers']);

        Route::get('home',[DashboardApiController::class,'index']);

        Route::post('project-list', [ProjectApiController::class, 'index']);
        Route::post('project-create-update',[ProjectApiController::class,'projectCreateAndUpdate']);
        Route::post('project-delete',[ProjectApiController::class,'destroyProject']);
        Route::post('project-status-update',[ProjectApiController::class,'projectStatusUpdate']);
        Route::post('project-details',[ProjectApiController::class,'projectDetails']);
        Route::post('project-activity',[ProjectApiController::class,'projectActivity']);
        Route::post('project-file-upload',[ProjectApiController::class,'projectFileUpload']);

        Route::post('task-list',[TaskApiController::class,'index']);
        Route::post('task-create-update',[TaskApiController::class,'taskCreateAndUpdate']);
        Route::post('task-details',[TaskApiController::class,'taskDetails']);
		Route::post('task-delete',[TaskApiController::class,'taskDelete']);
        Route::post('taskboard',[TaskApiController::class,'taskboard']);
        Route::post('task-stage-update',[TaskApiController::class,'taskStageUpdate']);

    });
});