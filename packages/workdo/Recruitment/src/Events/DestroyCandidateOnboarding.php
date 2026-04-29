<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\CandidateOnboarding;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyCandidateOnboarding
{
    use Dispatchable;

    public function __construct(
        public CandidateOnboarding $candidateOnboarding
    ) {}
}