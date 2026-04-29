<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;

class CreateKnowledgeBaseCategory
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Request $request,
        public KnowledgeBaseCategory $knowledgeBaseCategory
    ) {}
}