<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\InterviewRound;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyInterviewRound
{
    use Dispatchable;

    public function __construct(
        public InterviewRound $interviewRound
    ) {}
}