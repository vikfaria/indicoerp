<?php

namespace Workdo\ProductService\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\ProductService\Models\ProductServiceItem;

class CreateProductServiceItem
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public ProductServiceItem $item
    ) {}
}
