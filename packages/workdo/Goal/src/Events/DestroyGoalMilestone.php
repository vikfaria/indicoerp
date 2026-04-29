<?php

namespace Workdo\Goal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Goal\Models\GoalMilestone;

class DestroyGoalMilestone
{
    use Dispatchable;

    public function __construct(
        public GoalMilestone $milestone
    ) {}
}