<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\InterviewFeedback;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateInterviewFeedback
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public InterviewFeedback $interviewFeedback
    ) {}
}