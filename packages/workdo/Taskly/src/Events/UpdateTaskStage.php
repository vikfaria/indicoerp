<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Taskly\Models\TaskStage;

class UpdateTaskStage
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public TaskStage $taskStage
    ) {}
}