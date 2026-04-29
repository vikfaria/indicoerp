<?php

namespace Workdo\Contract\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;
use Workdo\Contract\Models\Contract;

class CreateContract
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Contract $contract
    ) {}
}