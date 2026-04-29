<?php

namespace Workdo\Account\Listeners;

use App\Events\CreateTransfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Workdo\Account\Services\JournalService;

class CreateTransferListener
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function handle(CreateTransfer $event)
    {
        if(Module_is_active('Account'))
        {
            // Load required relationships
            $event->transfer->load(['fromWarehouse', 'toWarehouse', 'product']);
            $this->journalService->createStockTransferJournal($event->transfer);
        }
    }
}
