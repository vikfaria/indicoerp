<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\DealCall;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class DealCallUpdate
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public DealCall $dealCall
    ) {}
}