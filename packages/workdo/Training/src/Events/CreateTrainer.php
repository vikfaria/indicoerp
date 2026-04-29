<?php

namespace Workdo\Training\Events;

use Workdo\Training\Models\Trainer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateTrainer
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Trainer $trainer
    ) {}
}