<?php

namespace Workdo\Goal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Goal\Models\GoalContribution;

class CreateGoalContribution
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public GoalContribution $goalContribution
    ) {}
}
