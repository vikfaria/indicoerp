<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\SupportTicket\Models\QuickLink;

class CreateQuickLink
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public QuickLink $quickLink
    ) {}
}