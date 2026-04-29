<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\HrmDocument;

class DestroyDocument
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public HrmDocument $hrmDocument
    )
    {
        //
    }
}