<?php

namespace Workdo\LandingPage\Events;

use Workdo\LandingPage\Models\NewsletterSubscriber;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateNewsletterSubscriber
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public NewsletterSubscriber $subscriber
    ) {}
}