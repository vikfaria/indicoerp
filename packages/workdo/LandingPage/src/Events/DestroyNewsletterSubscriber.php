<?php

namespace Workdo\LandingPage\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Workdo\LandingPage\Models\NewsletterSubscriber;

class DestroyNewsletterSubscriber
{
    use Dispatchable;

    public function __construct(
        public NewsletterSubscriber $subscriber,
    ) {}
}