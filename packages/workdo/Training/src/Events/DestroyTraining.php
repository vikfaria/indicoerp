<?php

namespace Workdo\Training\Events;

use Workdo\Training\Models\Training;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyTraining
{
    use Dispatchable;

    public function __construct(
        public Training $training
    ) {}
}