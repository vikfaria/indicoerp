<?php

namespace Workdo\Timesheet\Events;

use Workdo\Timesheet\Models\Timesheet;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateTimesheet
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Timesheet $timesheet
    ) {}
}