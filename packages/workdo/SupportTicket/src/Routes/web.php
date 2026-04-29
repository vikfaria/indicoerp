<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Workdo\SupportTicket\Http\Controllers\ContactController;
use Workdo\SupportTicket\Http\Controllers\ConversationController;
use Workdo\SupportTicket\Http\Controllers\CustomPageController;
use Workdo\SupportTicket\Http\Controllers\DashboardController;
use Workdo\SupportTicket\Http\Controllers\FaqController;
use Workdo\SupportTicket\Http\Controllers\FrontendController;
use Workdo\SupportTicket\Http\Controllers\KnowledgebaseCategoryController;
use Workdo\SupportTicket\Http\Controllers\KnowledgeController;
use Workdo\SupportTicket\Http\Controllers\SupportTicketController;
use Workdo\SupportTicket\Http\Controllers\TicketCategoryController;
use Workdo\SupportTicket\Http\Controllers\Company\SettingsController;
use Workdo\SupportTicket\Http\Controllers\SupportTicketSettingController;
use Workdo\SupportTicket\Http\Controllers\TitleSectionController;
use Workdo\SupportTicket\Http\Controllers\CtaSectionController;
use Workdo\SupportTicket\Http\Controllers\QuickLinkController;
use Workdo\SupportTicket\Http\Controllers\SupportInformationController;
use Workdo\SupportTicket\Http\Controllers\ContactInformationController;
use Workdo\SupportTicket\Http\Middleware\SupportTicketSharedDataMiddleware;

Route::group(['middleware' => ['web', 'auth', 'verified', 'PlanModuleCheck:SupportTicket']], function () {
    Route::resource('support-tickets', SupportTicketController::class)->except(['show']);
    
    Route::prefix('support-ticket/ticket-categories')->name('ticket-category.')->group(function () {
        Route::get('/', [TicketCategoryController::class, 'index'])->name('index');
        Route::post('/', [TicketCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [TicketCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [TicketCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('support-ticket/support-categories')->name('support-category.')->group(function () {
        Route::get('/', [TicketCategoryController::class, 'supportCategories'])->name('index');
    });

    Route::prefix('support-ticket/knowledge-categories')->name('knowledge-category.')->group(function () {
        Route::get('/', [KnowledgebaseCategoryController::class, 'index'])->name('index');
        Route::post('/', [KnowledgebaseCategoryController::class, 'store'])->name('store');
        Route::put('/{category}', [KnowledgebaseCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [KnowledgebaseCategoryController::class, 'destroy'])->name('destroy');
    });
    
    Route::resource('support-ticket-faq', FaqController::class);
    Route::resource('support-ticket-knowledge', KnowledgeController::class);
    Route::resource('support-ticket-contact', ContactController::class)->except(['show']);
    Route::get('support-ticket-contact/{contact}/show', [ContactController::class, 'show'])->name('support-ticket-contact.show');
                                                                                
    Route::get('/dashboard/support-ticket', [DashboardController::class, 'index'])->name('dashboard.support-tickets');
    Route::get('/dashboard/support-ticket/client', [DashboardController::class, 'clientDashboard'])->name('dashboard.support-tickets.client');
    Route::get('/dashboard/support-ticket/vendor', [DashboardController::class, 'vendorDashboard'])->name('dashboard.support-tickets.vendor');
    Route::get('/dashboard/support-ticket/staff', [DashboardController::class, 'staffDashboard'])->name('dashboard.support-tickets.staff');

    Route::post('/custom-fields', [SupportTicketController::class, 'storeCustomFields'])->name('support-ticket.store');
    Route::get('support-tickets/list/search/{status?}', [SupportTicketController::class, 'index'])->name('support-tickets.search');
    Route::delete('support-tickets-attachment/{tid}/destroy/{id}', [SupportTicketController::class, 'attachmentDestroy'])->name('support-tickets.attachment.destroy');
    
    Route::post('support-ticket/{id}/note', [SupportTicketController::class, 'storeNote'])->name('support-ticket.note.store');
    Route::post('support-ticket/setting/store', [SupportTicketController::class, 'storeCustomFields'])->name('support-ticket.setting.store');
    Route::post('support-ticket/settings/update', [SettingsController::class, 'update'])->name('support-ticket.settings.update');
    Route::get('support-ticket/fields/get', [SettingsController::class, 'getFields'])->name('support-ticket.fields.get');
    Route::get('support-ticket/grid/{status?}', [SupportTicketController::class, 'grid'])->name('support-tickets.grid');
    Route::post('support-ticket/getUser', [SupportTicketController::class, 'getUserData'])->name('support-tickets.getuser');
    Route::post('support-ticket/download-attachment', [SupportTicketController::class, 'downloadAttachment'])->name('support-ticket.attachment.download');
    Route::post('support-ticket/{ticketId}/send-conversion', [SupportTicketController::class, 'storeconverison'])->name('support-ticket.admin-send-conversion.store');
    Route::get('support-ticket/{ticketId}/reply/{conversionId}/edit', [SupportTicketController::class, 'editReply'])->name('support-ticket.reply.edit');
    Route::put('support-ticket/{ticketId}/reply/{conversionId}', [SupportTicketController::class, 'updateReply'])->name('support-ticket.reply.update');
    Route::delete('support-ticket/{ticketId}/reply/{conversionId}', [SupportTicketController::class, 'deleteReply'])->name('support-ticket.reply.delete');

    // Brand Settings Routes
    Route::get('support-ticket/settings/brand', [SupportTicketSettingController::class, 'brandSettings'])->name('support-ticket.settings.brand');
    Route::post('support-ticket/settings/brand', [SupportTicketSettingController::class, 'updateBrandSettings'])->name('support-ticket.settings.brand.update');


    // Custom Pages Routes
    Route::prefix('support-ticket/custom-pages')->name('support-ticket.custom-pages.')->group(function () {
        Route::get('/', [CustomPageController::class, 'index'])->name('index');
        Route::post('/', [CustomPageController::class, 'store'])->name('store');
        Route::put('/{id}', [CustomPageController::class, 'update'])->name('update');
        Route::delete('/{id}', [CustomPageController::class, 'destroy'])->name('destroy');
    });

    // Title Sections Routes
    Route::prefix('support-ticket/title-sections')->name('support-ticket.title-sections.')->group(function () {
        Route::get('/', [TitleSectionController::class, 'index'])->name('index');
        Route::post('/', [TitleSectionController::class, 'store'])->name('store');
    });

    // CTA Sections Routes
    Route::prefix('support-ticket/cta-sections')->name('support-ticket.cta-sections.')->group(function () {
        Route::get('/', [CtaSectionController::class, 'index'])->name('index');
        Route::post('/', [CtaSectionController::class, 'store'])->name('store');
    });

    // Quick Links Routes
    Route::prefix('support-ticket/quick-links')->name('support-ticket.quick-links.')->group(function () {
        Route::get('/', [QuickLinkController::class, 'index'])->name('index');
        Route::post('/', [QuickLinkController::class, 'store'])->name('store');
        Route::put('/{id}', [QuickLinkController::class, 'update'])->name('update');
        Route::delete('/{id}', [QuickLinkController::class, 'destroy'])->name('destroy');
    });

    // Support Information Routes
    Route::prefix('support-ticket/support-information')->name('support-ticket.support-information.')->group(function () {
        Route::get('/', [SupportInformationController::class, 'index'])->name('index');
        Route::post('/', [SupportInformationController::class, 'store'])->name('store');
    });

    // Contact Information Routes
    Route::prefix('support-ticket/contact-information')->name('support-ticket.contact-information.')->group(function () {
        Route::get('/', [ContactInformationController::class, 'index'])->name('index');
        Route::post('/', [ContactInformationController::class, 'store'])->name('store');
    });

    Route::get('knowledge/import/export', [KnowledgeController::class, 'fileImportExport'])->name('knowledge.file.import');
    Route::post('knowledge/import', [KnowledgeController::class, 'fileImport'])->name('knowledge.import');
    Route::get('knowledge/import/modal', [KnowledgeController::class, 'fileImportModal'])->name('knowledge.import.modal');
    Route::post('knowledge/data/import/', [KnowledgeController::class, 'knowledgeImportdata'])->name('knowledge.import.data');

    Route::get('faq/import/export', [FaqController::class, 'fileImportExport'])->name('faq.file.import');
    Route::post('faq/import', [FaqController::class, 'fileImport'])->name('faq.import');
    Route::get('faq/import/modal', [FaqController::class, 'fileImportModal'])->name('faq.import.modal');
    Route::post('faq/data/import/', [FaqController::class, 'faqImportdata'])->name('faq.import.data');

    Route::get('knowledge-category/import', [KnowledgebaseCategoryController::class, 'fileImportExport'])->name('knowledge-category.import');
    Route::post('knowledge-category/import', [KnowledgebaseCategoryController::class, 'fileImport'])->name('knowledge-category.import.store');
    Route::post('knowledge-category/data/import/', [KnowledgebaseCategoryController::class, 'categoryImportdata'])->name('knowledge-category.import.data');
});

// Public Frontend Routes (separate from admin routes) - MOVED ABOVE SLUG ROUTES
Route::middleware(['web', SupportTicketSharedDataMiddleware::class])->prefix('{slug}/public-support')->name('support-ticket.')->group(function () {
    Route::get('/', [FrontendController::class, 'index'])->name('index');
    Route::get('/create', [FrontendController::class, 'create'])->name('create');
    Route::post('/store', [FrontendController::class, 'store'])->name('create.store');
    Route::get('/search', [FrontendController::class, 'search'])->name('search');
    Route::post('/search', [FrontendController::class, 'searchTicket'])->name('search.post');
    Route::get('/show/{ticket_id}', [FrontendController::class, 'showByTicketId'])->name('show.ticket');
    Route::get('show-ticket/{id}', [FrontendController::class, 'show'])->name('show');
    Route::get('/knowledge', [FrontendController::class, 'knowledge'])->name('knowledge');
    Route::get('/knowledge/{id}', [FrontendController::class, 'knowledgeArticle'])->name('knowledge.article');
    Route::get('/faq', [FrontendController::class, 'faq'])->name('faq');
    Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
    Route::get('/privacy-policy', [FrontendController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::get('/terms-conditions', [FrontendController::class, 'termsConditions'])->name('terms-conditions');
    Route::get('/page/{pageslug}', [FrontendController::class, 'customPage'])->name('custom-page');
    Route::post('/contact', [FrontendController::class, 'storeContact'])->name('contact.store');
    Route::post('/ticket/{id}/reply', [FrontendController::class, 'storeReply'])->name('reply');
    Route::post('/{ticketId}/send-conversion', [ConversationController::class, 'store'])->name('send-conversion.store');
    Route::post('{ticketId}/send-email', [ConversationController::class, 'sendEmail'])->name('send-email');
    Route::post('{ticketId}/reply-email', [ConversationController::class, 'replyEmail'])->name('reply-email');
});

