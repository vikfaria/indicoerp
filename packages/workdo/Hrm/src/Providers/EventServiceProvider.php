<?php

namespace Workdo\Hrm\Providers;

use App\Events\DefaultData;
use Workdo\Hrm\Listeners\DataDefault;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Events\GivePermissionToRole;
use Workdo\Hrm\Listeners\GiveRoleToPermission;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DefaultData::class => [
            DataDefault::class
        ],
         GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
    ];
}
