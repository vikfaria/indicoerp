<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\Interview;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateInterview
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Interview $interview
    ) {}
}