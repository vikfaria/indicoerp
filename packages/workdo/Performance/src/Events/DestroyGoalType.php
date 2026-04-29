<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Performance\Models\PerformanceGoalType;

class DestroyGoalType
{
    use Dispatchable;

    public function __construct(
        public PerformanceGoalType $goalType
    ) {}
}