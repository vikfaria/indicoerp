<?php

namespace Workdo\Retainer\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Workdo\Retainer\Events\CreateRetainerPayment;
use Workdo\Retainer\Events\UpdateRetainerPaymentStatus;
use Workdo\Retainer\Models\Retainer;
use Workdo\Retainer\Models\RetainerPayment;
use Workdo\Retainer\Models\RetainerPaymentAllocation;

class RetainerPaymentController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(Auth::check(), 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:users,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'status' => ['nullable', 'in:pending,cleared,cancelled'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.retainer_id' => ['required_with:allocations', 'exists:retainers,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ]);

        $payment = DB::transaction(function () use ($request, $validated) {
            $customer = User::query()
                ->where('id', $validated['customer_id'])
                ->where('type', 'client')
                ->where('created_by', creatorId())
                ->firstOrFail();

            $payment = RetainerPayment::query()->create([
                'customer_id' => $customer->id,
                'bank_account_id' => $validated['bank_account_id'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'payment_amount' => $validated['payment_amount'],
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
                'creator_id' => Auth::id(),
                'created_by' => creatorId(),
            ]);

            foreach ($validated['allocations'] ?? [] as $allocation) {
                $retainer = Retainer::query()
                    ->where('id', $allocation['retainer_id'])
                    ->where('customer_id', $customer->id)
                    ->where('created_by', creatorId())
                    ->firstOrFail();

                RetainerPaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'retainer_id' => $retainer->id,
                    'allocated_amount' => $allocation['amount'],
                    'creator_id' => Auth::id(),
                    'created_by' => creatorId(),
                ]);
            }

            CreateRetainerPayment::dispatch($request, $payment);

            if ($payment->status === 'cleared') {
                UpdateRetainerPaymentStatus::dispatch($request, $payment);
            }

            return $payment;
        });

        return back()->with('success', __('The retainer payment has been created successfully.'));
    }

    public function updateStatus(Request $request, RetainerPayment $retainerPayment)
    {
        abort_unless(Auth::check() && $retainerPayment->created_by === creatorId(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:cleared,cancelled'],
        ]);

        if ($retainerPayment->status !== 'pending') {
            return back()->with('error', __('Only pending retainer payments can be updated.'));
        }

        DB::transaction(function () use ($request, $retainerPayment, $validated): void {
            $retainerPayment->status = $validated['status'];
            $retainerPayment->save();

            if ($validated['status'] === 'cleared') {
                UpdateRetainerPaymentStatus::dispatch($request, $retainerPayment);
            }
        });

        return back()->with('success', __('The retainer payment status has been updated successfully.'));
    }
}
