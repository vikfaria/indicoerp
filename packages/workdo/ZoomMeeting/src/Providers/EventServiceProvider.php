<?php

namespace Workdo\ZoomMeeting\Providers;

use App\Events\GivePermissionToRole;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\ZoomMeeting\Listeners\GiveRoleToPermission;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Add your event listeners here
        GivePermissionToRole::class => [
            GiveRoleToPermission::class,
        ],
    ];
}