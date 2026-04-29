<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Models\VendorPaymentAllocation;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\DebitNoteApplication;
use Workdo\Account\Http\Requests\StoreVendorPaymentRequest;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;
use App\Models\User;
use App\Models\PurchaseInvoice;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Account\Events\CreateVendorPayment;
use Workdo\Account\Events\UpdateVendorPaymentStatus;
use Workdo\Account\Events\DestroyVendorPayment;

class VendorPaymentController extends Controller
{
    protected $journalService;
    protected $bankTransactionsService;

    public function __construct(JournalService $journalService, BankTransactionsService $bankTransactionsService)
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-vendor-payments')){
            $query = VendorPayment::with(['vendor', 'bankAccount', 'allocations.invoice', 'debitNoteApplications.debitNote'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-vendor-payments')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-vendor-payments')) {
                        $q->where('creator_id', Auth::id())->orWhere('vendor_id',Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Apply filters
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->search) {
                $query->where('payment_number', 'like', '%' . $request->search . '%');
            }
            if ($request->date_from) {
                $query->whereDate('payment_date', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('payment_date', '<=', $request->date_to);
            }
            if ($request->bank_account_id) {
                $query->where('bank_account_id', $request->bank_account_id);
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $payments = $query->paginate($request->get('per_page', 10));
            $vendors = User::where('type', 'vendor')->where('created_by', creatorId())->get();

            $bankAccounts = BankAccount::where('is_active', true)->where('created_by', creatorId())->get();

            return Inertia::render('Account/VendorPayments/Index', [
                'payments' => $payments,
                'vendors' => $vendors,
                'bankAccounts' => $bankAccounts,
                'filters' => $request->only(['vendor_id', 'status', 'search', 'bank_account_id'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreVendorPaymentRequest $request)
    {
        if(Auth::user()->can('create-vendor-payments')){
            $payment = DB::transaction(function () use ($request) {
                $payment = new VendorPayment();
                $payment->payment_date = $request->payment_date;
                $payment->vendor_id = $request->vendor_id;
                $payment->bank_account_id = $request->bank_account_id;
                $payment->payment_method = $request->payment_method;
                $payment->mobile_money_provider = $request->payment_method === 'mobile_money' ? $request->mobile_money_provider : null;
                $payment->mobile_money_number = $request->payment_method === 'mobile_money' ? $request->mobile_money_number : null;
                $payment->reference_number = $request->reference_number;
                $payment->payment_amount = $request->payment_amount;
                $payment->notes = $request->notes;
                $payment->creator_id = Auth::id();
                $payment->created_by = creatorId();
                $payment->save();

                foreach ($request->input('allocations', []) as $allocation) {
                    $paymentAllocation = new VendorPaymentAllocation();
                    $paymentAllocation->payment_id = $payment->id;
                    $paymentAllocation->invoice_id = $allocation['invoice_id'];
                    $paymentAllocation->allocated_amount = $allocation['amount'];
                    $paymentAllocation->save();
                }

                foreach ($request->input('debit_notes', []) as $debitNote) {
                    DebitNoteApplication::create([
                        'debit_note_id' => $debitNote['debit_note_id'],
                        'payment_id' => $payment->id,
                        'applied_amount' => $debitNote['amount'],
                        'application_date' => $request->payment_date,
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId()
                    ]);
                }

                return $payment;
            });

            // Dispatch event
            CreateVendorPayment::dispatch($request, $payment);

            return redirect()->route('account.vendor-payments.index')->with('success', __('The vendor payment has been created successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getOutstandingInvoices($vendorId)
    {
        abort_unless(Auth::user()->can('create-vendor-payments'), 403);

        $vendor = User::query()
            ->where('id', $vendorId)
            ->where('type', 'vendor')
            ->where('created_by', creatorId())
            ->firstOrFail();

        $invoices = PurchaseInvoice::where('vendor_id', $vendorId)
            ->where('balance_amount', '>', 0)
            ->whereIn('status', ['posted', 'partial'])
            ->where('created_by', creatorId())
            ->get()
            ->map(fn (PurchaseInvoice $invoice) => $this->serialiseOutstandingPurchaseInvoice($invoice, $vendor));

        $debitNotes = DebitNote::where('vendor_id', $vendorId)
            ->where('balance_amount', '>', 0)
            ->whereIn('status', ['approved', 'partial'])
            ->where('created_by', creatorId())
            ->get()
            ->map(fn (DebitNote $debitNote) => $this->serialiseOutstandingDebitNote($debitNote, $vendor));

        return response()->json([
            'invoices' => $invoices,
            'debitNotes' => $debitNotes
        ]);
    }

    public function updateStatus(Request $request, VendorPayment $vendorPayment)
    {
        if(Auth::user()->can('cleared-vendor-payments') && $vendorPayment->created_by == creatorId()){
            try {
                $request->validate([
                    'status' => 'required|string|in:cleared,cancelled',
                ]);

                if ($vendorPayment->status !== 'pending') {
                    return back()->with('error', __('Only pending vendor payments can be updated.'));
                }

                DB::transaction(function () use ($request, $vendorPayment) {
                    if($request->status === 'cleared') {
                        $vendorPayment->loadMissing('allocations.invoice', 'debitNoteApplications.debitNote');

                        if($vendorPayment->payment_amount > 0)
                        {
                            $this->journalService->createVendorPaymentJournal($vendorPayment);
                            $this->bankTransactionsService->createVendorPayment($vendorPayment);
                        }

                        foreach ($vendorPayment->allocations as $allocation) {
                            $invoice = $allocation->invoice;
                            $invoice->paid_amount += $allocation->allocated_amount;
                            $invoice->balance_amount = $invoice->total_amount - $invoice->paid_amount;

                            if ($invoice->balance_amount == 0) {
                                $invoice->status = 'paid';
                            } elseif ($invoice->paid_amount > 0) {
                                $invoice->status = 'partial';
                            }
                            $invoice->save();
                        }

                        foreach ($vendorPayment->debitNoteApplications as $debitNoteApplication) {
                            $debitNoteModel = $debitNoteApplication->debitNote;

                            if (!$debitNoteModel) {
                                continue;
                            }

                            $debitNoteModel->applied_amount += $debitNoteApplication->applied_amount;
                            $debitNoteModel->balance_amount = $debitNoteModel->total_amount - $debitNoteModel->applied_amount;
                            $debitNoteModel->status = $debitNoteModel->balance_amount <= 0 ? 'applied' : 'partial';
                            $debitNoteModel->save();
                        }
                    }

                    $vendorPayment->update(['status' => $request->status]);
                });

                 // Dispatch event
                 UpdateVendorPaymentStatus::dispatch($request, $vendorPayment);

                return back()->with('success', __('The payment status are updated successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(VendorPayment $vendorPayment)
    {
        if(Auth::user()->can('delete-vendor-payments') && $vendorPayment->created_by == creatorId() && $vendorPayment->status === 'pending'){

            // Dispatch event before deletion
            DestroyVendorPayment::dispatch($vendorPayment);

            $vendorPayment->delete();
            return back()->with('success', __('The vendor payment has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    private function serialiseOutstandingPurchaseInvoice(PurchaseInvoice $invoice, User $vendor): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date?->toDateString(),
            'total_amount' => $invoice->total_amount,
            'balance_amount' => $invoice->balance_amount,
            'status' => $invoice->status,
            'counterparty_name' => data_get($invoice->counterparty_snapshot, 'company_name')
                ?: data_get($invoice->counterparty_snapshot, 'name')
                ?: $vendor->name,
            'counterparty_tax_label' => data_get($invoice->counterparty_snapshot, 'tax_label'),
            'counterparty_tax_number' => data_get($invoice->counterparty_snapshot, 'tax_number'),
        ];
    }

    private function serialiseOutstandingDebitNote(DebitNote $debitNote, User $vendor): array
    {
        return [
            'id' => $debitNote->id,
            'debit_note_number' => $debitNote->debit_note_number,
            'debit_note_date' => $debitNote->debit_note_date?->toDateString(),
            'total_amount' => $debitNote->total_amount,
            'balance_amount' => $debitNote->balance_amount,
            'status' => $debitNote->status,
            'counterparty_name' => data_get($debitNote->counterparty_snapshot, 'company_name')
                ?: data_get($debitNote->counterparty_snapshot, 'name')
                ?: $vendor->name,
            'counterparty_tax_label' => data_get($debitNote->counterparty_snapshot, 'tax_label'),
            'counterparty_tax_number' => data_get($debitNote->counterparty_snapshot, 'tax_number'),
        ];
    }
}
