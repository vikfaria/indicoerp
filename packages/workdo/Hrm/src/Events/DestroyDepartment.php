<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Department;

class DestroyDepartment
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Department $department
    )
    {
        //
    }
}