<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\Interview;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyInterview
{
    use Dispatchable;

    public function __construct(
        public Interview $interview
    ) {}
}