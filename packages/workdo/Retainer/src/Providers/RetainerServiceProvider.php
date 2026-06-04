<?php

namespace Workdo\Retainer\Providers;

use Illuminate\Support\ServiceProvider;

class RetainerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $routesPath = __DIR__ . '/../Routes/web.php';
        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }

        $migrationsPath = __DIR__ . '/../Database/Migrations';
        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
