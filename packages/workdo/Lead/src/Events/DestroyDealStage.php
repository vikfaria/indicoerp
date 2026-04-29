<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\DealStage;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyDealStage
{
    use Dispatchable;

    public function __construct(
        public DealStage $dealStage
    ) {}
}