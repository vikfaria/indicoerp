<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\SupportTicket\Models\KnowledgeBase;

class CreateKnowledgeBase
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public KnowledgeBase $knowledgeBase
    ) {}
}