<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class UpdateBrandSettings
{
    use Dispatchable, SerializesModels;
    public function __construct(
        public Request $request,
        public $settings
    ) {}
}