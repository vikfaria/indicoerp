<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\ChecklistItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateChecklistItem
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public ChecklistItem $checklistItem
    ) {}
}