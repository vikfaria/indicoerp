<?php

namespace Workdo\FormBuilder\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\GivePermissionToRole;
use Workdo\FormBuilder\Listeners\GiveRoleToPermission;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Form conversion events
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
    ];
}
