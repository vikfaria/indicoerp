<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Account\Models\ChartOfAccount;

class DestroyChartOfAccount
{
    use Dispatchable;

    public function __construct(
        public ChartOfAccount $chartofaccount
    ) {}
}
