<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateResignation
{
    use Dispatchable, SerializesModels;

    public function __construct()
    {
        //
    }
}