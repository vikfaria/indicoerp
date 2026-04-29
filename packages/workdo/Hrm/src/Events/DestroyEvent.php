<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Event;

class DestroyEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Event $event
    )
    {
        //
    }
}