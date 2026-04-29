<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\Revenue;

class DestroyRevenue
{
    use Dispatchable;

    public function __construct(
        public Revenue $revenue
    ) {}
}
