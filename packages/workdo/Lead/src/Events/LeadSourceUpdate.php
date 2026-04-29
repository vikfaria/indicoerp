<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class LeadSourceUpdate
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Lead $lead
    ) {}
}