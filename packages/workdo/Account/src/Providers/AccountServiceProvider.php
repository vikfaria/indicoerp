<?php

namespace Workdo\Account\Providers;

use Illuminate\Support\ServiceProvider;
use Workdo\Account\Models\BankTransaction;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\Expense;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\MozFiscalClosing;
use Workdo\Account\Models\MozTaxAccountMapping;
use Workdo\Account\Models\Revenue;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Observers\AccountCacheInvalidationObserver;
use Workdo\Account\Observers\JournalEntryObserver;

class AccountServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $routesPath = __DIR__.'/../Routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
        
        $migrationsPath = __DIR__.'/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        JournalEntry::observe(JournalEntryObserver::class);
        $this->registerCacheInvalidationObservers();
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }

    private function registerCacheInvalidationObservers(): void
    {
        $observer = AccountCacheInvalidationObserver::class;
        $models = [
            BankTransaction::class,
            CreditNote::class,
            Customer::class,
            CustomerPayment::class,
            DebitNote::class,
            Expense::class,
            JournalEntry::class,
            MozFiscalClosing::class,
            MozTaxAccountMapping::class,
            Revenue::class,
            Vendor::class,
            VendorPayment::class,
            'App\\Models\\PurchaseInvoice',
            'App\\Models\\PurchaseReturn',
            'App\\Models\\SalesInvoice',
            'App\\Models\\SalesInvoiceReturn',
        ];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $modelClass::observe($observer);
        }
    }
}
