<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\Taskly\Models\TaskStage;

class DestroyTaskStage
{
    use Dispatchable;

    public function __construct(
        public TaskStage $taskStage,
    ) {}
}