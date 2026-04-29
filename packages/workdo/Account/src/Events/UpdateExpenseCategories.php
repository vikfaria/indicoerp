<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\ExpenseCategories;

class UpdateExpenseCategories
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public ExpenseCategories $expenseCategories
    ) {}
}
