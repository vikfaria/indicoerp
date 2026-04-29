<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Pipeline;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyPipeline
{
    use Dispatchable;

    public function __construct(
        public Pipeline $pipeline
    ) {}
}