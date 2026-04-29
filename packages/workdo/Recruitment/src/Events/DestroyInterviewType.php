<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\InterviewType;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyInterviewType
{
    use Dispatchable;

    public function __construct(
        public InterviewType $interviewType
    ) {}
}