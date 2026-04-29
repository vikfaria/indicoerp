<?php

namespace Workdo\DoubleEntry\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\DoubleEntry\Models\BalanceSheet;

class CreateBalanceSheet
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public BalanceSheet $balanceSheet
    ) {}
}
