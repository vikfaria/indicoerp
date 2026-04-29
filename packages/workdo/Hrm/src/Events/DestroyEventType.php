<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\EventType;

class DestroyEventType
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public EventType $eventType
    )
    {
        //
    }
}