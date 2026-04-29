<?php

namespace Workdo\DoubleEntry\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\DoubleEntry\Models\BalanceSheet;

class DestroyBalanceSheet
{
    use Dispatchable;

    public function __construct(
        public BalanceSheet $balanceSheet
    ) {}
}
