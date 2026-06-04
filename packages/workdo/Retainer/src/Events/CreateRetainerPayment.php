<?php

namespace Workdo\Retainer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Retainer\Models\RetainerPayment;

class CreateRetainerPayment
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public RetainerPayment $retainerPayment
    ) {}
}
