<?php

use Illuminate\Support\Facades\Route;
use Workdo\SupportTicket\Http\Controllers\Api\FaqApiController;
use Workdo\SupportTicket\Http\Controllers\Api\KnowledgeApiController;
use Workdo\SupportTicket\Http\Controllers\Api\TicketApiContollerController;
use Workdo\SupportTicket\Http\Controllers\Api\DashboardApiController;

Route::prefix('api')->middleware(['api.json'])->group(function () {
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'support-ticket'], function () {

        Route::get('home', [DashboardApiController::class, 'index']);
        Route::get('get-request-data', [TicketApiContollerController::class, 'getRequestData']);

        Route::get('tickets', [TicketApiContollerController::class, 'index']);
        Route::post('ticket/store', [TicketApiContollerController::class, 'store']);
        Route::post('ticket/update/{ticket_id}', [TicketApiContollerController::class, 'update']);
        Route::post('ticket/delete/{ticket_id}', [TicketApiContollerController::class, 'destroy']);

        Route::post('ticket/note/store/{ticket_id}', [TicketApiContollerController::class, 'storeNote']);
        Route::post('ticket/add-reply/{ticket_id}', [TicketApiContollerController::class, 'addReply']);

        Route::get('knowledges', [KnowledgeApiController::class, 'index']);
        Route::post('knowledge/store', [KnowledgeApiController::class, 'store']);
        Route::post('knowledge/update/{knowledge_id}', [KnowledgeApiController::class, 'update']);
        Route::post('knowledge/delete/{knowledge_id}', [KnowledgeApiController::class, 'destroy']);

        Route::get('faqs', [FaqApiController::class, 'index']);
        Route::post('faqs/store', [FaqApiController::class, 'store']);
        Route::post('faq/update/{faq_id}', [FaqApiController::class, 'update']);
        Route::post('faq/delete/{faq_id}', [FaqApiController::class, 'destroy']);
    });
});
