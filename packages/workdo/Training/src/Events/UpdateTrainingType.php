<?php

namespace Workdo\Training\Events;

use Workdo\Training\Models\TrainingType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateTrainingType
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public TrainingType $trainingType
    ) {}
}