<?php

namespace Workdo\Goal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Goal\Models\GoalContribution;

class DestroyGoalContribution
{
    use Dispatchable;

    public function __construct(
        public GoalContribution $goalContribution
    ) {}
}
