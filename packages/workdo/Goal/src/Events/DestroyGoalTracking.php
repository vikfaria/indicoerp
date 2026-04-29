<?php

namespace Workdo\Goal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Goal\Models\GoalTracking;

class DestroyGoalTracking
{
    use Dispatchable;

    public function __construct(
        public GoalTracking $tracking
    ) {}
}
