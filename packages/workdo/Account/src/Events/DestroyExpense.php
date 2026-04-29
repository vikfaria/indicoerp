<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\Expense;

class DestroyExpense
{
    use Dispatchable;

    public function __construct(
        public Expense $expense
    ) {}
}
