<?php

namespace Workdo\Account\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Workdo\Account\Models\BankTransfer;

class CreateBankTransfer
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public BankTransfer $bankTransfer
    ) {}
}