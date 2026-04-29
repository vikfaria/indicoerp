<?php

namespace Workdo\ZoomMeeting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\ZoomMeeting\Models\ZoomMeeting;

class DestroyZoomMeeting
{
    use Dispatchable;

    public function __construct(
        public ZoomMeeting $meeting,
    ) {}
}