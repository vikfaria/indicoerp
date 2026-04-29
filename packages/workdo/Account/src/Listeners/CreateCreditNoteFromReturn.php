<?php

namespace Workdo\Account\Listeners;

use App\Events\ApproveSalesReturn;
use App\Services\DocumentFiscalSnapshotService;
use Illuminate\Support\Facades\Auth;
use Workdo\Account\Models\CreditNote;

class CreateCreditNoteFromReturn
{
    public function __construct(
        private readonly DocumentFiscalSnapshotService $documentFiscalSnapshotService
    ) {
    }

    public function handle(ApproveSalesReturn $event): void
    {
        $salesReturn = $event->salesReturn->loadMissing('items.taxes', 'originalInvoice');

        // Create credit note from sales return in approved status
        $creditNote = new CreditNote();
        $creditNote->credit_note_number = $salesReturn->return_number;
        $creditNote->credit_note_date = $salesReturn->return_date;
        $creditNote->customer_id = $salesReturn->customer_id;
        $creditNote->invoice_id = $salesReturn->original_invoice_id;
        $creditNote->return_id = $salesReturn->id;
        $creditNote->establishment_id = $salesReturn->warehouse_id;
        $creditNote->reason = 'Sales return - ' . $salesReturn->reason;
        $creditNote->status = 'draft';
        $creditNote->subtotal = $salesReturn->subtotal;
        $creditNote->tax_amount = $salesReturn->tax_amount;
        $creditNote->discount_amount = $salesReturn->discount_amount;
        $creditNote->total_amount = $salesReturn->total_amount;
        $creditNote->applied_amount = 0;
        $creditNote->balance_amount = $salesReturn->total_amount;
        $creditNote->notes = $salesReturn->notes;
        $creditNote->creator_id = Auth::id();
        $creditNote->created_by = creatorId();
        $creditNote->save();
        $this->documentFiscalSnapshotService->syncCreditNote($creditNote->loadMissing('salesReturn.originalInvoice', 'invoice'));

        // Copy items from return
        foreach ($salesReturn->items as $returnItem) {
            $creditNoteItem = $creditNote->items()->create([
                'product_id' => $returnItem->product_id,
                'quantity' => $returnItem->return_quantity,
                'unit_price' => $returnItem->unit_price,
                'discount_percentage' => $returnItem->discount_percentage ?? 0,
                'discount_amount' => $returnItem->discount_amount ?? 0,
                'tax_percentage' => $returnItem->tax_percentage ?? 0,
                'tax_amount' => $returnItem->tax_amount,
                'total_amount' => $returnItem->total_amount
            ]);

            // Copy taxes
            foreach ($returnItem->taxes as $tax) {
                $creditNoteItem->taxes()->create([
                    'tax_name' => $tax->tax_name,
                    'tax_rate' => $tax->tax_rate
                ]);
            }
        }
    }
}
