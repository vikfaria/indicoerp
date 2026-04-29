<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\CustomQuestion;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyCustomQuestion
{
    use Dispatchable;

    public function __construct(
        public CustomQuestion $customQuestion
    ) {}
}