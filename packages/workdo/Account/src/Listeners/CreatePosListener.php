<?php

namespace Workdo\Account\Listeners;

use App\Services\InventoryCostingService;
use Workdo\Account\Models\BankAccount;
use Workdo\Pos\Events\CreatePos;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;
use Workdo\Account\Models\ChartOfAccount;

class CreatePosListener
{
    protected $journalService;
    protected $bankTransactionsService;
    protected InventoryCostingService $inventoryCostingService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService, InventoryCostingService $inventoryCostingService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
        $this->inventoryCostingService = $inventoryCostingService;
    }

    public function handle(CreatePos $event)
    {
        if (Module_is_active('Account')) {
            $posSale = $event->posSale->loadMissing('items');
            $companyId = (int) ($posSale->created_by ?? creatorId());
            $warehouseCode = $posSale->warehouse_id ? (string) $posSale->warehouse_id : null;

            foreach ($posSale->items as $item) {
                $quantity = (float) $item->quantity;
                if ($quantity <= 0) {
                    continue;
                }

                $this->inventoryCostingService->recordSale(
                    $companyId,
                    (int) $item->product_id,
                    $quantity,
                    (string) ($posSale->pos_date ?? now()->toDateString()),
                    'pos_sale',
                    (int) $posSale->id,
                    $warehouseCode,
                    false
                );
            }

            $bankAccount = BankAccount::where('id', $event->posSale->bank_account_id)
                ->where('created_by', creatorId())
                ->first();

            if ($bankAccount) {
                $this->bankTransactionsService->createPosPayment($event->posSale, $bankAccount->id);
            }

            $this->journalService->createPosJournal($event->posSale);
            $this->journalService->createPosCOGSJournal($event->posSale);
        }
    }
}
