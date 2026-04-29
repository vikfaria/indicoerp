<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Designation;

class DestroyDesignation
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Designation $designation
    )
    {
        //
    }
}