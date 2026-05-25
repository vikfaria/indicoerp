<?php

namespace App\Providers;

use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Observers\FiscalDocumentObserver;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (($this->app->isLocal() || $this->app->hasDebugModeEnabled()) && class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPerformanceMonitoring();

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

    private function registerPerformanceMonitoring(): void
    {
        if (! config('performance.enabled')) {
            return;
        }

        if (app()->runningInConsole()) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            $threshold = (int) config('performance.slow_query_ms', 800);

            if ($query->time < $threshold) {
                return;
            }

            Log::channel('performance')->warning('Slow query detected', [
                'connection' => $query->connectionName,
                'duration_ms' => round($query->time, 2),
                'threshold_ms' => $threshold,
                'sql' => substr((string) $query->sql, 0, 600),
                'bindings_count' => count($query->bindings),
            ]);
        });

        $totalThreshold = (int) config('performance.slow_query_total_ms', 1500);
        DB::whenQueryingForLongerThan($totalThreshold, function (Connection $connection, QueryExecuted $event) use ($totalThreshold): void {
            Log::channel('performance')->warning('Cumulative query time exceeded threshold', [
                'connection' => $connection->getName(),
                'threshold_ms' => $totalThreshold,
                'last_query_ms' => round($event->time, 2),
                'last_sql' => substr((string) $event->sql, 0, 400),
                'last_bindings_count' => count($event->bindings),
            ]);
        });
    }
}
