<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Overtime;

class DestroyOverTime
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Overtime $overtime
    )
    {
        //
    }
}