<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyLeadSource
{
    use Dispatchable;

    public function __construct(
        public Lead $lead,
    ) {}
}