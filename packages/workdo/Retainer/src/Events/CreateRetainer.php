<?php

namespace Workdo\Retainer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Retainer\Models\Retainer;

class CreateRetainer
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Retainer $retainer
    ) {}
}
