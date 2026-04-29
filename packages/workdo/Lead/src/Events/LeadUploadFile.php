<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\LeadFile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class LeadUploadFile
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Lead $lead,
    ) {}
}