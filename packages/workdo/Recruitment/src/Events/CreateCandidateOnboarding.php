<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\CandidateOnboarding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateCandidateOnboarding
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public CandidateOnboarding $candidateOnboarding
    ) {}
}