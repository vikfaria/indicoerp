<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\TaskComment;

class DestroyTaskComment
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TaskComment $comment
    ) {}
}
