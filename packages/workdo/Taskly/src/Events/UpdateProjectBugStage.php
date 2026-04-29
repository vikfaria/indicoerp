<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\ProjectBug;

class UpdateProjectBugStage
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public ProjectBug $bug
    ) {}
}
