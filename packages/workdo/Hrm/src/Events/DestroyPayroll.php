<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Payroll;

class DestroyPayroll
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Payroll $payroll
    )
    {
        //
    }
}