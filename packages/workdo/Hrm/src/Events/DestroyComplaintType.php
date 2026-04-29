<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\ComplaintType;

class DestroyComplaintType
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public ComplaintType $complaintType
    )
    {
        //
    }
}