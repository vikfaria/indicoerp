<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\DealStage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateDealStage
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public DealStage $dealStage
    ) {}
}