<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\LeaveApplication;

class DestroyLeaveApplication
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public LeaveApplication $leaveapplication
    )
    {
        //
    }
}