<?php

namespace Workdo\Account\Services;

use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\DebitNoteApplication;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\Auth;

class DebitNoteService
{
    public function autoApplyToInvoice($invoiceId)
    {
        $invoice = PurchaseInvoice::find($invoiceId);
        $availableDebitNotes = DebitNote::where('vendor_id', $invoice->vendor_id)
            ->where('status', 'approved')
            ->where('balance_amount', '>', 0)
            ->orderBy('debit_note_date', 'asc')
            ->get();

        $totalApplied = 0;

        foreach ($availableDebitNotes as $debitNote) {
            $applyAmount = min($debitNote->balance_amount, $invoice->total_amount - $totalApplied);
            
            if ($applyAmount <= 0) break;

            DebitNoteApplication::create([
                'debit_note_id' => $debitNote->id,
                'invoice_id' => $invoice->id,
                'applied_amount' => $applyAmount,
                'application_date' => now(),
                'creator_id' => Auth::id(),
                'created_by' => creatorId()
            ]);

            $debitNote->applied_amount += $applyAmount;
            $debitNote->balance_amount -= $applyAmount;
            if ($debitNote->balance_amount <= 0) {
                $debitNote->status = 'applied';
            }
            $debitNote->save();

            $totalApplied += $applyAmount;

            if ($totalApplied >= $invoice->total_amount) break;
        }

        $invoice->debit_note_applied = $totalApplied;
        $invoice->balance_amount = $invoice->total_amount - $totalApplied;
        $invoice->save();

        return $totalApplied;
    }

    public function getAvailableForVendor($vendorId)
    {
        return DebitNote::where('vendor_id', $vendorId)
            ->where('status', 'approved')
            ->where('balance_amount', '>', 0)
            ->where('created_by', creatorId())
            ->orderBy('debit_note_date', 'asc')
            ->get();
    }
}