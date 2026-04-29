<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\LeadCall;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyLeadCall
{
    use Dispatchable;

    public function __construct(
        public LeadCall $leadCall
    ) {}
}