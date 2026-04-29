<?php

namespace Workdo\DoubleEntry\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\DoubleEntry\Models\BalanceSheetNote;

class DestroyBalanceSheetNote
{
    use Dispatchable;

    public function __construct(
        public BalanceSheetNote $balanceSheetNote
    ) {}
}
