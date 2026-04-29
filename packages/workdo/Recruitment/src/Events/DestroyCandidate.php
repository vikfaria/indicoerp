<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\Candidate;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyCandidate
{
    use Dispatchable;

    public function __construct(
        public Candidate $candidate
    ) {}
}