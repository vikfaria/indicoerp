<?php

namespace Workdo\Recruitment\Events;

use Workdo\Recruitment\Models\ChecklistItem;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyChecklistItem
{
    use Dispatchable;

    public function __construct(
        public ChecklistItem $checklistItem
    ) {}
}