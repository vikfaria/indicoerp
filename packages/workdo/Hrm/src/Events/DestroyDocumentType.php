<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestroyDocumentType
{
    use Dispatchable, SerializesModels;

    public function __construct()
    {
        //
    }
}