<?php

namespace Workdo\Training\Events;

use Workdo\Training\Models\TrainingType;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyTrainingType
{
    use Dispatchable;

    public function __construct(
        public TrainingType $trainingType
    ) {}
}