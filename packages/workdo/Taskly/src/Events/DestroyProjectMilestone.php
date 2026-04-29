<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\ProjectMilestone;

class DestroyProjectMilestone
{
    use Dispatchable;

    public function __construct(
        public ProjectMilestone $milestone
    ) {}
}
