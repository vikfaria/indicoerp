<?php

namespace Workdo\Recruitment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateBrandSetting
{
    use Dispatchable;

    public function __construct(
        public Request $request
    ) {}
}