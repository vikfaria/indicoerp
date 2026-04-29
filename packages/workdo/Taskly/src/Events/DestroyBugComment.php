<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\BugComment;

class DestroyBugComment
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public BugComment $comment
    ) {}
}
