<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Hrm\Models\Termination;

class CreateTermination
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public Termination $termination
    ) {}
}