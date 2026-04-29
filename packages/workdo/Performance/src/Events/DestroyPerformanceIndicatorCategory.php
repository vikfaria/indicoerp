<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Performance\Models\PerformanceIndicatorCategory;

class DestroyPerformanceIndicatorCategory
{
    use Dispatchable;

    public function __construct(
        public PerformanceIndicatorCategory $category
    ) {}
}