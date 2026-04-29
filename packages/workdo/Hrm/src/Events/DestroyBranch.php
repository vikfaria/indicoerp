<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Branch;

class DestroyBranch
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Branch $branch
    )
    {
        //
    }
}