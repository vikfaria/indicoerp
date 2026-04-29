<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Holiday;

class DestroyHoliday
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Holiday $holiday
    )
    {
        //
    }
}