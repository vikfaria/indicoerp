<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\ProjectBug;

class DestroyProjectBug
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProjectBug $bug
    ) {}
}
