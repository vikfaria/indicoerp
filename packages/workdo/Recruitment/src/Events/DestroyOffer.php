<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\Offer;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyOffer
{
    use Dispatchable;

    public function __construct(
        public Offer $offer
    ) {}
}