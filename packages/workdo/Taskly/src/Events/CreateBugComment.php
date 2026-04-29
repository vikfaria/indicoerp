<?php

namespace Workdo\Taskly\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Taskly\Models\BugComment;

class CreateBugComment
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public BugComment $comment
    ) {}
}
