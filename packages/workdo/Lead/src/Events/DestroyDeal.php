<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Deal;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyDeal
{
    use Dispatchable;

    public function __construct(
        public Deal $deal
    ) {}
}