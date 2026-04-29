<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\LeadFile;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyLeadFile
{
    use Dispatchable;

    public function __construct(
        public Lead $lead,
    ) {}
}