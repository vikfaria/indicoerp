<?php

namespace Workdo\Lead\Providers;

use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Lead\Listeners\DataDefault;
use Workdo\Lead\Listeners\GiveRoleToPermission;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
        DefaultData::class => [
            DataDefault::class,
        ],
    ];
}