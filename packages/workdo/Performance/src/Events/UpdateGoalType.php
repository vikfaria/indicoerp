<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Performance\Models\PerformanceGoalType;

class UpdateGoalType
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public PerformanceGoalType $goalType
    ) {}
}