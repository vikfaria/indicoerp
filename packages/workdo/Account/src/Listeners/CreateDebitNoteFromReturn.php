<?php

namespace Workdo\Account\Listeners;

use App\Events\ApprovePurchaseReturn;
use App\Services\DocumentFiscalSnapshotService;
use Illuminate\Support\Facades\Auth;
use Workdo\Account\Models\DebitNote;

class CreateDebitNoteFromReturn
{
    public function __construct(
        private readonly DocumentFiscalSnapshotService $documentFiscalSnapshotService
    ) {
    }

    public function handle(ApprovePurchaseReturn $event): void
    {
        $purchaseReturn = $event->return->loadMissing('items.taxes', 'originalInvoice');

        // Create debit note from purchase return in draft status
        $debitNote = new DebitNote();
        $debitNote->debit_note_number = $purchaseReturn->return_number;
        $debitNote->debit_note_date = $purchaseReturn->return_date;
        $debitNote->vendor_id = $purchaseReturn->vendor_id;
        $debitNote->invoice_id = $purchaseReturn->original_invoice_id;
        $debitNote->return_id = $purchaseReturn->id;
        $debitNote->establishment_id = $purchaseReturn->warehouse_id;
        $debitNote->reason = 'Purchase return - ' . $purchaseReturn->reason;
        $debitNote->status = 'draft';
        $debitNote->subtotal = $purchaseReturn->subtotal;
        $debitNote->tax_amount = $purchaseReturn->tax_amount;
        $debitNote->discount_amount = $purchaseReturn->discount_amount;
        $debitNote->total_amount = $purchaseReturn->total_amount;
        $debitNote->applied_amount = 0;
        $debitNote->balance_amount = $purchaseReturn->total_amount;
        $debitNote->notes = $purchaseReturn->notes;
        $debitNote->creator_id = Auth::id();
        $debitNote->created_by = creatorId();
        $debitNote->save();
        $this->documentFiscalSnapshotService->syncDebitNote($debitNote->loadMissing('purchaseReturn.originalInvoice', 'invoice'));

        // Copy items from return
        foreach ($purchaseReturn->items as $returnItem) {
            $debitNoteItem = $debitNote->items()->create([
                'product_id' => $returnItem->product_id,
                'quantity' => $returnItem->return_quantity,
                'unit_price' => $returnItem->unit_price,
                'discount_percentage' => $returnItem->discount_percentage ?? 0,
                'discount_amount' => $returnItem->discount_amount ?? 0,
                'tax_percentage' => $returnItem->tax_percentage ?? 0,
                'tax_amount' => $returnItem->tax_amount,
                'total_amount' => $returnItem->total_amount,
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            // Copy taxes
            foreach ($returnItem->taxes as $tax) {
                $debitNoteItem->taxes()->create([
                    'tax_name' => $tax->tax_name,
                    'tax_rate' => $tax->tax_rate
                ]);
            }
        }
    }
}
