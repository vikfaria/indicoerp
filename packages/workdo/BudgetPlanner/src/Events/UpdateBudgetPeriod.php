<?php

namespace Workdo\BudgetPlanner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\BudgetPlanner\Models\BudgetPeriod;

class UpdateBudgetPeriod
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public BudgetPeriod $budget_period
    ) {}
}
