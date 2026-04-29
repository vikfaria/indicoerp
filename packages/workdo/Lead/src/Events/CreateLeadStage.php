<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\LeadStage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateLeadStage
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public LeadStage $leadStage
    ) {}
}