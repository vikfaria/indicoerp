<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\InterviewRound;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateInterviewRound
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public InterviewRound $interviewRound
    ) {}
}
