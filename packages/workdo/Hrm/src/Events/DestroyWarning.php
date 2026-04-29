<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Warning;

class DestroyWarning
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Warning $warning
    )
    {
        //
    }
}