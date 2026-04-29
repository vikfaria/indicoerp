<?php

namespace Workdo\BudgetPlanner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\BudgetPlanner\Models\BudgetAllocation;

class DestroyBudgetAllocation
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BudgetAllocation $budget_allocation
    ) {}
}
