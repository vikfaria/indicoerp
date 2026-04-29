<?php

namespace Workdo\Goal\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Account\Events\UpdateBudgetSpending;
use Workdo\Goal\Listeners\UpdateBudgetSpendingLis;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UpdateBudgetSpending::class => [
            UpdateBudgetSpendingLis::class,
        ],
    ];
}
