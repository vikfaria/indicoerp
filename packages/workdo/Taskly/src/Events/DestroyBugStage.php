<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Taskly\Models\BugStage;

class DestroyBugStage
{
    use Dispatchable;

    public function __construct(
        public BugStage $bugStage,
    ) {}
}