<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Taskly\Models\BugStage;

class CreateBugStage
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public BugStage $bugStage
    ) {}
}