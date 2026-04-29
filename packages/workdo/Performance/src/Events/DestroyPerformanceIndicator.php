<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Performance\Models\PerformanceIndicator;

class DestroyPerformanceIndicator
{
    use Dispatchable;

    public function __construct(
        public PerformanceIndicator $indicator
    ) {}
}