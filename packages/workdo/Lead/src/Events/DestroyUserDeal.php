<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyUserDeal
{
    use Dispatchable;

    public function __construct(
        public Deal $deal,
    ) {}
}