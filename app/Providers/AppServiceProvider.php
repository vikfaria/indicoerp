<?php

namespace App\Providers;

use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Observers\FiscalDocumentObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register fiscal document observers for SCE Moçambique compliance
        try {
            if (Schema::hasTable('fiscal_document_types')) {
                SalesInvoice::observe(FiscalDocumentObserver::class);
                PurchaseInvoice::observe(FiscalDocumentObserver::class);
                
                // Also register for returns if tables exist
                if (Schema::hasTable('sales_invoice_returns')) {
                    \App\Models\SalesInvoiceReturn::observe(FiscalDocumentObserver::class);
                }
                if (Schema::hasTable('purchase_returns')) {
                    \App\Models\PurchaseReturn::observe(FiscalDocumentObserver::class);
                }
                if (Schema::hasTable('credit_notes')) {
                    \Workdo\Account\Models\CreditNote::observe(FiscalDocumentObserver::class);
                }
                if (Schema::hasTable('debit_notes')) {
                    \Workdo\Account\Models\DebitNote::observe(FiscalDocumentObserver::class);
                }
            }
        } catch (\Throwable $e) {
            // Ignore DB connection/schema exceptions during bootstrap (e.g. during migrations/CLI setup)
        }
    }
}
