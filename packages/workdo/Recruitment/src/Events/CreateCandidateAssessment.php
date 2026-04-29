<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\CandidateAssessment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateCandidateAssessment
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public CandidateAssessment $candidateAssessment
    ) {}
}