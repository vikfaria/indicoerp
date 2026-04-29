<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\LeaveType;

class DestroyLeaveType
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public LeaveType $leavetype
    )
    {
        //
    }
}