<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class DealAddUser
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Deal $deal,
    ) {}
}