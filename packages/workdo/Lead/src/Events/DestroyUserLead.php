<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyUserLead
{
    use Dispatchable;

    public function __construct(
        public Lead $lead,
    ) {}
}