<?php

namespace Workdo\DoubleEntry\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class YearEndClose
{
    use Dispatchable;

    public function __construct(
        public Request $request,
    ) {}
}
