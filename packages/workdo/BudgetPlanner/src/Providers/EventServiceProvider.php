<?php

namespace Workdo\BudgetPlanner\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Workdo\Account\Events\UpdateBudgetSpending;
use Workdo\BudgetPlanner\Listeners\UpdateBudgetSpendingLis;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UpdateBudgetSpending::class => [
            UpdateBudgetSpendingLis::class,
        ],
    ];
}
