<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Deduction;

class DestroyDeduction
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Deduction $deduction
    )
    {
        //
    }
}