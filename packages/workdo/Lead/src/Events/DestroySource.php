<?php

namespace Workdo\Lead\Events;

use Workdo\Lead\Models\Source;
use Illuminate\Foundation\Events\Dispatchable;

class DestroySource
{
    use Dispatchable;

    public function __construct(
        public Source $source
    ) {}
}