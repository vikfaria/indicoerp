<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\Project;

class ProjectDeleteShareToClient
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Project $project
    ) {}
}
