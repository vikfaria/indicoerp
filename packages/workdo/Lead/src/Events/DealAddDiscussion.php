<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\DealDiscussion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class DealAddDiscussion
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Deal $deal,
    ) {}
}