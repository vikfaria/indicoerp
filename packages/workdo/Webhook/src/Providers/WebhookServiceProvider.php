<?php

namespace Workdo\Webhook\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Workdo\Webhook\Listeners\WebhookEventListener;

class WebhookServiceProvider extends ServiceProvider
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
        
        $this->registerWebhookEvents();
    }
    
    private function registerWebhookEvents()
    {
        $listener = [WebhookEventListener::class, 'handle'];
        $events = config('webhook-events.events', []);
        
        foreach ($events as $eventClass => $config) {
            if (class_exists($eventClass)) {
                Event::listen($eventClass, $listener);
            }
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/webhook-events.php', 'webhook-events');
    }
}