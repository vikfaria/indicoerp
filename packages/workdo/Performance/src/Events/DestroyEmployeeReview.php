<?php

namespace Workdo\Performance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Performance\Models\PerformanceEmployeeReview;

class DestroyEmployeeReview
{
    use Dispatchable;

    public function __construct(
        public PerformanceEmployeeReview $review
    ) {}
}