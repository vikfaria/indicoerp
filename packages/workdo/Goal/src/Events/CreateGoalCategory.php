<?php

namespace Workdo\Goal\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Goal\Models\GoalCategory;

class CreateGoalCategory
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public GoalCategory $category
    ) {}
}
