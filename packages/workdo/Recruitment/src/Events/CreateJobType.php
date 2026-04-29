<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\JobType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateJobType
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public JobType $jobtype
    ) {}
}