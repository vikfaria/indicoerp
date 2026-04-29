<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\LoanType;

class DestroyLoanType
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public LoanType $loanType
    )
    {
        //
    }
}