<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Performance\Models\PerformanceEmployeeGoal;

class DestroyEmployeeGoal
{
    use Dispatchable;

    public function __construct(
        public PerformanceEmployeeGoal $goal
    ) {}
}