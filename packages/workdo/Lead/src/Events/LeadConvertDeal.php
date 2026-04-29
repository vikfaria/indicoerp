<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class LeadConvertDeal
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Lead $lead,
    ) {}
}