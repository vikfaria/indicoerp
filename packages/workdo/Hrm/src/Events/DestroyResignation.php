<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestroyResignation
{
    use Dispatchable, SerializesModels;

    public function __construct()
    {
        //
    }
}