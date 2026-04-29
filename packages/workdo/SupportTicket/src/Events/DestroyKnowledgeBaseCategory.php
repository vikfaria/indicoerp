<?php

namespace Workdo\SupportTicket\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Workdo\SupportTicket\Models\KnowledgeBaseCategory;

class DestroyKnowledgeBaseCategory
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public KnowledgeBaseCategory $knowledgeBaseCategory
    ) {}
}