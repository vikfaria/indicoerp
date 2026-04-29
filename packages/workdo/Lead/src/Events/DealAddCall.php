<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\DealCall;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class DealAddCall
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Deal $deal,
    ) {}
}