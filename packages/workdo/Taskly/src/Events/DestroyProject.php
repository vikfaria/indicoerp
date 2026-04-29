<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\Project;

class DestroyProject
{
    use Dispatchable;

    public function __construct(
        public Project $project
    ) {}
}
