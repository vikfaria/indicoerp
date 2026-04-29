<?php

namespace Workdo\Training\Events;

use Workdo\Training\Models\Training;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateTraining
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Training $training
    ) {}
}