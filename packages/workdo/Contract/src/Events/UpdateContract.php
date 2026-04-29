<?php

namespace Workdo\Contract\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\Contract\Models\Contract;

class UpdateContract
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public Contract $contract
    ) {}
}
