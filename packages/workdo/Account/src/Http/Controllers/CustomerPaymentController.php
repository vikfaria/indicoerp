<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\CustomerPaymentAllocation;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\CreditNoteApplication;
use Workdo\Account\Http\Requests\StoreCustomerPaymentRequest;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;
use App\Models\User;
use App\Models\SalesInvoice;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Workdo\Account\Events\CreateCustomerPayment;
use Workdo\Account\Events\UpdateCustomerPaymentStatus;
use Workdo\Account\Events\DestroyCustomerPayment;

class CustomerPaymentController extends Controller
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
        if(Auth::user()->can('manage-customer-payments')){
            $query = CustomerPayment::with(['customer', 'bankAccount', 'allocations.invoice', 'creditNoteApplications.creditNote'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-customer-payments')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-customer-payments')) {
                        $q->where('creator_id', Auth::id())->orWhere('customer_id',Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Apply filters
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
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
            $customers = User::where('type', 'client')->where('created_by', creatorId())->get();

            $bankAccounts = BankAccount::where('is_active', true)->where('created_by', creatorId())->get();

            return Inertia::render('Account/CustomerPayments/Index', [
                'payments' => $payments,
                'customers' => $customers,
                'bankAccounts' => $bankAccounts,
                'filters' => $request->only(['customer_id', 'status', 'search', 'bank_account_id'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCustomerPaymentRequest $request)
    {
        if(Auth::user()->can('create-customer-payments')){
            $payment = DB::transaction(function () use ($request) {
                $payment = new CustomerPayment();
                $payment->payment_date = $request->payment_date;
                $payment->customer_id = $request->customer_id;
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
                    $paymentAllocation = new CustomerPaymentAllocation();
                    $paymentAllocation->payment_id = $payment->id;
                    $paymentAllocation->invoice_id = $allocation['invoice_id'];
                    $paymentAllocation->allocated_amount = $allocation['amount'];
                    $paymentAllocation->save();
                }

                foreach ($request->input('credit_notes', []) as $creditNote) {
                    CreditNoteApplication::create([
                        'credit_note_id' => $creditNote['credit_note_id'],
                        'payment_id' => $payment->id,
                        'applied_amount' => $creditNote['amount'],
                        'application_date' => $request->payment_date,
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId()
                    ]);
                }

                return $payment;
            });

            // Dispatch event
            CreateCustomerPayment::dispatch($request, $payment);

            return redirect()->route('account.customer-payments.index')->with('success', __('The customer payment has been created successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }



    public function getOutstandingInvoices($customerId)
    {
        abort_unless(Auth::user()->can('create-customer-payments'), 403);

        $customer = User::query()
            ->where('id', $customerId)
            ->where('type', 'client')
            ->where('created_by', creatorId())
            ->firstOrFail();

        $invoices = SalesInvoice::where('customer_id', $customerId)
            ->where('balance_amount', '>', 0)
            ->whereIn('status', ['posted', 'partial'])
            ->where('created_by', creatorId())
            ->get()
            ->map(fn (SalesInvoice $invoice) => $this->serialiseOutstandingSalesInvoice($invoice, $customer));

        $creditNotes = CreditNote::where('customer_id', $customerId)
            ->where('balance_amount', '>', 0)
            ->whereIn('status', ['approved', 'partial'])
            ->where('created_by', creatorId())
            ->get()
            ->map(fn (CreditNote $creditNote) => $this->serialiseOutstandingCreditNote($creditNote, $customer));

        return response()->json([
            'invoices' => $invoices,
            'creditNotes' => $creditNotes
        ]);
    }

    public function updateStatus(Request $request, CustomerPayment $customerPayment)
    {
        if(Auth::user()->can('cleared-customer-payments') && $customerPayment->created_by == creatorId()){
            try {
                $request->validate([
                    'status' => 'required|string|in:cleared,cancelled',
                ]);

                if ($customerPayment->status !== 'pending') {
                    return back()->with('error', __('Only pending customer payments can be updated.'));
                }

                DB::transaction(function () use ($request, $customerPayment) {
                    if($request->status === 'cleared') {
                        $customerPayment->loadMissing('allocations.invoice', 'creditNoteApplications.creditNote');

                        if($customerPayment->payment_amount > 0)
                        {
                            $this->journalService->createCustomerPaymentJournal($customerPayment);
                            $this->bankTransactionsService->createCustomerPayment($customerPayment);
                        }

                        foreach ($customerPayment->allocations as $allocation) {
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

                        foreach ($customerPayment->creditNoteApplications as $creditNoteApplication) {
                            $creditNoteModel = $creditNoteApplication->creditNote;

                            if (!$creditNoteModel) {
                                continue;
                            }

                            $creditNoteModel->applied_amount += $creditNoteApplication->applied_amount;
                            $creditNoteModel->balance_amount = $creditNoteModel->total_amount - $creditNoteModel->applied_amount;
                            $creditNoteModel->status = $creditNoteModel->balance_amount <= 0 ? 'applied' : 'partial';
                            $creditNoteModel->save();
                        }
                    }

                    $customerPayment->update(['status' => $request->status]);
                });

                 // Dispatch event
                 UpdateCustomerPaymentStatus::dispatch($request, $customerPayment);

                return back()->with('success', __('The payment status are updated successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function destroy(CustomerPayment $customerPayment)
    {
        if(Auth::user()->can('delete-customer-payments') && $customerPayment->created_by == creatorId() && $customerPayment->status === 'pending'){

            // Dispatch event before deletion
            DestroyCustomerPayment::dispatch($customerPayment);

            $customerPayment->delete();
            return back()->with('success', __('The customer payment has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    private function serialiseOutstandingSalesInvoice(SalesInvoice $invoice, User $customer): array
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
                ?: $customer->name,
            'counterparty_tax_label' => data_get($invoice->counterparty_snapshot, 'tax_label'),
            'counterparty_tax_number' => data_get($invoice->counterparty_snapshot, 'tax_number'),
        ];
    }

    private function serialiseOutstandingCreditNote(CreditNote $creditNote, User $customer): array
    {
        return [
            'id' => $creditNote->id,
            'credit_note_number' => $creditNote->credit_note_number,
            'credit_note_date' => $creditNote->credit_note_date?->toDateString(),
            'total_amount' => $creditNote->total_amount,
            'balance_amount' => $creditNote->balance_amount,
            'status' => $creditNote->status,
            'counterparty_name' => data_get($creditNote->counterparty_snapshot, 'company_name')
                ?: data_get($creditNote->counterparty_snapshot, 'name')
                ?: $customer->name,
            'counterparty_tax_label' => data_get($creditNote->counterparty_snapshot, 'tax_label'),
            'counterparty_tax_number' => data_get($creditNote->counterparty_snapshot, 'tax_number'),
        ];
    }
}
