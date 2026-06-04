<?php

namespace Workdo\Retainer\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Workdo\Retainer\Events\ConvertSalesRetainer;
use Workdo\Retainer\Events\CreateRetainer;
use Workdo\Retainer\Models\Retainer;
use Workdo\Retainer\Models\RetainerPaymentAllocation;

class RetainerController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(Auth::check(), 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:users,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'retainer_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:retainer_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'balance_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,sent,accepted,rejected,converted,paid,partial'],
            'notes' => ['nullable', 'string'],
        ]);

        $retainer = DB::transaction(function () use ($request, $validated) {
            $customer = User::query()
                ->where('id', $validated['customer_id'])
                ->where('type', 'client')
                ->where('created_by', creatorId())
                ->firstOrFail();

            $retainer = Retainer::query()->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'retainer_date' => $validated['retainer_date'],
                'due_date' => $validated['due_date'] ?? null,
                'subtotal' => $validated['subtotal'],
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'total_amount' => $validated['total_amount'],
                'balance_amount' => $validated['balance_amount'] ?? $validated['total_amount'],
                'status' => $validated['status'] ?? 'draft',
                'notes' => $validated['notes'] ?? null,
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            CreateRetainer::dispatch($request, $retainer);

            return $retainer;
        });

        return back()->with('success', __('The retainer has been created successfully.'));
    }

    public function sent(Retainer $retainer)
    {
        return $this->updateStatus($retainer, 'sent', __('The retainer has been sent successfully.'));
    }

    public function accept(Retainer $retainer)
    {
        return $this->updateStatus($retainer, 'accepted', __('The retainer has been accepted successfully.'));
    }

    public function reject(Retainer $retainer)
    {
        return $this->updateStatus($retainer, 'rejected', __('The retainer has been rejected successfully.'));
    }

    public function duplicate(Request $request, Retainer $retainer)
    {
        abort_unless(Auth::check() && $retainer->created_by === creatorId(), 403);

        $copy = DB::transaction(function () use ($retainer) {
            $copy = $retainer->replicate([
                'id',
                'retainer_number',
                'status',
                'created_at',
                'updated_at',
            ]);
            $copy->status = 'draft';
            $copy->balance_amount = $copy->total_amount;
            $copy->creator_id = Auth::id();
            $copy->created_by = creatorId();
            $copy->save();

            return $copy;
        });

        CreateRetainer::dispatch($request, $copy);

        return back()->with('success', __('The retainer has been duplicated successfully.'));
    }

    public function convertToInvoice(Request $request, Retainer $retainer)
    {
        abort_unless(Auth::check() && $retainer->created_by === creatorId(), 403);

        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:sales_invoices,id'],
        ]);

        $invoice = SalesInvoice::query()
            ->where('id', $validated['invoice_id'])
            ->where('customer_id', $retainer->customer_id)
            ->where('created_by', creatorId())
            ->firstOrFail();

        $hasClearedAllocations = RetainerPaymentAllocation::query()
            ->where('retainer_id', $retainer->id)
            ->whereHas('payment', function ($query): void {
                $query->where('status', 'cleared');
            })
            ->exists();

        abort_unless($hasClearedAllocations, 422, __('At least one cleared retainer payment allocation is required before conversion.'));

        DB::transaction(function () use ($invoice, $retainer): void {
            ConvertSalesRetainer::dispatch($invoice, $retainer);
        });

        return back()->with('success', __('The retainer has been converted to an invoice successfully.'));
    }

    private function updateStatus(Retainer $retainer, string $status, string $message)
    {
        abort_unless(Auth::check() && $retainer->created_by === creatorId(), 403);

        DB::transaction(function () use ($retainer, $status): void {
            $retainer->status = $status;
            $retainer->save();
        });

        return back()->with('success', $message);
    }
}
