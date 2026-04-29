<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\InterviewType;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateInterviewType
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public InterviewType $interviewType
    ) {}
}