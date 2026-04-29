<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Pipeline;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreatePipeline
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Pipeline $pipeline
    ) {}
}