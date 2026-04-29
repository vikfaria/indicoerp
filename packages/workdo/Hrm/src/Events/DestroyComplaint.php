<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Complaint;

class DestroyComplaint
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Complaint $complaint
    )
    {
        //
    }
}