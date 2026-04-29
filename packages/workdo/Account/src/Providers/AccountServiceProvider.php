<?php

namespace Workdo\Account\Providers;

use Illuminate\Support\ServiceProvider;
use Workdo\Account\Models\JournalEntry;
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
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }
}
