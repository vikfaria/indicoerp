<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Performance\Models\PerformanceReviewCycle;

class DestroyReviewCycle
{
    use Dispatchable;

    public function __construct(
        public PerformanceReviewCycle $cycle
    ) {}
}