<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Employee;

class DestroyEmployee
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Employee $employee
    )
    {
        //
    }
}