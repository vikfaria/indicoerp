<?php

namespace Workdo\GoogleCaptcha\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Add your event listeners here
        // Example:
        // App\Events\SomeEvent::class => [
        //     Workdo\GoogleCaptcha\Listeners\SomeListener::class,
        // ],
    ];
}