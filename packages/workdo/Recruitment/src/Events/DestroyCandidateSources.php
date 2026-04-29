<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\CandidateSources;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyCandidateSources
{
    use Dispatchable;

    public function __construct(
        public CandidateSources $candidateSources
    ) {}
}