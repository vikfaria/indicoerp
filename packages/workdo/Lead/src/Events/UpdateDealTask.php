<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\DealTask;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateDealTask
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public DealTask $dealTask
    ) {}
}