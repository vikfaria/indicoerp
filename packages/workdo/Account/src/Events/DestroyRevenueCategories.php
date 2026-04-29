<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Account\Models\RevenueCategories;

class DestroyRevenueCategories
{
    use Dispatchable;

    public function __construct(
        public RevenueCategories $revenuecategories
    ) {}
}
