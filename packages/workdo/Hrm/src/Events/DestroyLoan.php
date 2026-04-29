<?php

namespace Workdo\Hrm\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\Hrm\Models\Loan;

class DestroyLoan
{
    use Dispatchable, SerializesModels;

    public function __construct(
          public Loan $loan
    )
    {
        //
    }
}