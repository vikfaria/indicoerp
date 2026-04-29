<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\DealFile;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyDealFile
{
    use Dispatchable;

    public function __construct(
        public Deal $deal,
    ) {}
}