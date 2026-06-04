<?php

namespace Workdo\Account\Http\Controllers;

use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\CustomerPaymentAllocation;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\Customer as CustomerProfile;
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
use App\Services\ExchangeControlDossierService;
use Workdo\Hrm\Models\Branch;

class CustomerPaymentController extends Controller
{
    protected $journalService;
    protected $bankTransactionsService;
    protected $exchangeControlDossierService;

    public function __construct(
        JournalService $journalService,
        BankTransactionsService $bankTransactionsService,
        ExchangeControlDossierService $exchangeControlDossierService
    )
    {
        $this->journalService = $journalService;
        $this->bankTransactionsService = $bankTransactionsService;
        $this->exchangeControlDossierService = $exchangeControlDossierService;
    }

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-customer-payments')){
            $query = CustomerPayment::with(['customer', 'bankAccount.branch', 'branch', 'allocations.invoice', 'creditNoteApplications.creditNote'])
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
            $customerProfiles = CustomerProfile::query()
                ->where('created_by', creatorId())
                ->get()
                ->keyBy('user_id');

            $customers = User::where('type', 'client')
                ->where('created_by', creatorId())
                ->get()
                ->map(function (User $customerUser) use ($customerProfiles): array {
                    $profile = $customerProfiles->get($customerUser->id);

                    return [
                        'id' => $customerUser->id,
                        'name' => $profile?->company_name ?: $customerUser->name,
                        'email' => $customerUser->email,
                        'fiscal_residency_status' => $profile?->fiscal_residency_status ?? 'resident',
                        'fiscal_country' => $profile?->fiscal_country,
                    ];
                })
                ->values();

            $bankAccounts = BankAccount::where('is_active', true)
                ->where('created_by', creatorId())
                ->with('branch')
                ->get();

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
                $fxPayload = $this->resolveFxPayload($request);
                $fxCompliancePayload = $this->resolveFxCompliancePayload($request);
                $gifimPayload = $this->resolveGifimCompliancePayload($request, (float) $fxPayload['amount_mzn']);
                $approvalPayload = $this->resolveApprovalWorkflowPayload($request, $fxPayload, $fxCompliancePayload, $gifimPayload);

                $payment = new CustomerPayment();
                $payment->payment_date = $request->payment_date;
                $payment->customer_id = $request->customer_id;
                $payment->bank_account_id = $request->bank_account_id;
                $payment->branch_id = $this->resolveBankAccountBranchId((int) $request->bank_account_id);
                $payment->payment_method = $request->payment_method;
                $payment->mobile_money_provider = $request->payment_method === 'mobile_money' ? $request->mobile_money_provider : null;
                $payment->mobile_money_number = $request->payment_method === 'mobile_money' ? $request->mobile_money_number : null;
                $payment->reference_number = $request->reference_number;
                $payment->payment_amount = $request->payment_amount;
                $payment->currency_code = $fxPayload['currency_code'];
                $payment->exchange_rate = $fxPayload['exchange_rate'];
                $payment->foreign_amount = $fxPayload['foreign_amount'];
                $payment->amount_mzn = $fxPayload['amount_mzn'];
                $payment->fx_difference_amount = $fxPayload['fx_difference_amount'];
                $payment->is_export_receipt = $fxCompliancePayload['is_export_receipt'];
                $payment->receipt_origin_country = $fxCompliancePayload['receipt_origin_country'];
                $payment->export_reference = $fxCompliancePayload['export_reference'];
                $payment->intermediary_bank = $fxCompliancePayload['intermediary_bank'];
                $payment->repatriation_status = $fxCompliancePayload['repatriation_status'];
                $payment->repatriated_amount_mzn = $fxCompliancePayload['repatriated_amount_mzn'];
                $payment->fx_compliance_reference = $fxCompliancePayload['fx_compliance_reference'];
                $payment->gifim_alert_required = $gifimPayload['gifim_alert_required'];
                $payment->gifim_alert_category = $gifimPayload['gifim_alert_category'];
                $payment->gifim_alert_status = $gifimPayload['gifim_alert_status'];
                $payment->gifim_reference = $gifimPayload['gifim_reference'];
                $payment->gifim_reported_at = $gifimPayload['gifim_reported_at'];
                $payment->gifim_reported_by = $gifimPayload['gifim_reported_by'];
                $payment->gifim_submitted_document = $gifimPayload['gifim_submitted_document'];
                $payment->gifim_justification = $gifimPayload['gifim_justification'];
                $payment->high_value_approval_reference = $gifimPayload['high_value_approval_reference'];
                $payment->approval_required = $approvalPayload['approval_required'];
                $payment->approval_status = $approvalPayload['approval_status'];
                $payment->approval_risk_flags = $approvalPayload['approval_risk_flags'];
                $payment->approval_requested_at = $approvalPayload['approval_requested_at'];
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

                $this->exchangeControlDossierService->syncInboundCustomerPayment($payment);

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

    private function resolveBankAccountBranchId(int $bankAccountId): ?int
    {
        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', creatorId())
            ->first();

        if (!$bankAccount) {
            return null;
        }

        if ((int) ($bankAccount->branch_id ?? 0) > 0) {
            return (int) $bankAccount->branch_id;
        }

        $branchName = trim((string) $bankAccount->branch_name);
        if ($branchName === '') {
            return null;
        }

        return Branch::query()
            ->where('created_by', creatorId())
            ->whereRaw('LOWER(TRIM(branch_name)) = ?', [strtolower($branchName)])
            ->value('id');
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

                if (
                    $request->status === 'cleared'
                    && $customerPayment->approval_required
                    && $customerPayment->approval_status !== 'approved'
                ) {
                    return back()->with('error', __('Payment must be approved before it can be marked as cleared.'));
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

    public function approve(Request $request, CustomerPayment $customerPayment)
    {
        if (!Auth::user()->can('approve-customer-payments') || $customerPayment->created_by !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $request->validate([
            'approval_reference' => 'nullable|string|max:120',
        ]);

        if ($customerPayment->status !== 'pending') {
            return back()->with('error', __('Only pending customer payments can be approved.'));
        }

        if (!$customerPayment->approval_required) {
            return back()->with('error', __('This payment does not require approval.'));
        }

        DB::transaction(function () use ($request, $customerPayment): void {
            $approvalReference = trim((string) $request->input('approval_reference', ''));

            $payload = [
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'rejection_reason' => null,
                'rejected_at' => null,
                'rejected_by' => null,
            ];

            if ($approvalReference !== '') {
                $payload['approval_reference'] = $approvalReference;
            } elseif (empty($customerPayment->approval_reference) && !empty($customerPayment->high_value_approval_reference)) {
                $payload['approval_reference'] = $customerPayment->high_value_approval_reference;
            }

            $customerPayment->update($payload);
        });

        return back()->with('success', __('Payment approved successfully.'));
    }

    public function reject(Request $request, CustomerPayment $customerPayment)
    {
        if (!Auth::user()->can('approve-customer-payments') || $customerPayment->created_by !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        if ($customerPayment->status !== 'pending') {
            return back()->with('error', __('Only pending customer payments can be rejected.'));
        }

        if (!$customerPayment->approval_required) {
            return back()->with('error', __('This payment does not require approval.'));
        }

        if ($customerPayment->approval_status === 'approved') {
            return back()->with('error', __('Approved payments cannot be rejected.'));
        }

        $customerPayment->update([
            'approval_status' => 'rejected',
            'rejection_reason' => trim((string) $request->input('rejection_reason')),
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', __('Payment rejected successfully.'));
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

    private function resolveFxPayload(StoreCustomerPaymentRequest $request): array
    {
        $currencyCode = strtoupper((string) $request->input('currency_code', 'MZN'));
        if ($currencyCode === '') {
            $currencyCode = 'MZN';
        }

        $paymentAmount = round((float) $request->input('payment_amount', 0), 2);
        $exchangeRate = (float) $request->input('exchange_rate', 1);
        $foreignAmount = (float) $request->input('foreign_amount', $paymentAmount);

        if ($currencyCode === 'MZN') {
            return [
                'currency_code' => 'MZN',
                'exchange_rate' => 1,
                'foreign_amount' => $paymentAmount,
                'amount_mzn' => $paymentAmount,
                'fx_difference_amount' => 0,
            ];
        }

        $exchangeRate = $exchangeRate > 0 ? $exchangeRate : 1;
        $foreignAmount = $foreignAmount > 0 ? $foreignAmount : $paymentAmount;
        $convertedAmount = round($foreignAmount * $exchangeRate, 2);
        $fxDifference = round($paymentAmount - $convertedAmount, 2);

        return [
            'currency_code' => $currencyCode,
            'exchange_rate' => $exchangeRate,
            'foreign_amount' => $foreignAmount,
            'amount_mzn' => $paymentAmount,
            'fx_difference_amount' => $fxDifference,
        ];
    }

    private function resolveFxCompliancePayload(StoreCustomerPaymentRequest $request): array
    {
        $customerProfile = CustomerProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $request->input('customer_id'))
            ->first();

        $isExportReceipt = $request->boolean('is_export_receipt');

        if (!$isExportReceipt) {
            return [
                'is_export_receipt' => false,
                'receipt_origin_country' => null,
                'export_reference' => null,
                'intermediary_bank' => null,
                'repatriation_status' => 'not_applicable',
                'repatriated_amount_mzn' => null,
                'fx_compliance_reference' => null,
            ];
        }

        $repatriationStatus = (string) $request->input('repatriation_status', 'pending');
        $allowedStatuses = ['not_applicable', 'pending', 'partial', 'completed'];
        if (!in_array($repatriationStatus, $allowedStatuses, true)) {
            $repatriationStatus = 'pending';
        }

        $repatriatedAmount = $request->filled('repatriated_amount_mzn')
            ? round((float) $request->input('repatriated_amount_mzn'), 2)
            : null;

        return [
            'is_export_receipt' => true,
            'receipt_origin_country' => trim((string) $request->input('receipt_origin_country', (string) ($customerProfile?->fiscal_country ?? ''))),
            'export_reference' => trim((string) $request->input('export_reference')),
            'intermediary_bank' => trim((string) $request->input('intermediary_bank')),
            'repatriation_status' => $repatriationStatus,
            'repatriated_amount_mzn' => $repatriatedAmount,
            'fx_compliance_reference' => trim((string) $request->input('fx_compliance_reference')),
        ];
    }

    private function resolveApprovalWorkflowPayload(
        StoreCustomerPaymentRequest $request,
        array $fxPayload,
        array $fxCompliancePayload,
        array $gifimPayload
    ): array {
        $riskFlags = [];
        $currencyCode = strtoupper((string) ($fxPayload['currency_code'] ?? 'MZN'));
        $amountMzn = (float) ($fxPayload['amount_mzn'] ?? 0);
        $paymentMethod = strtolower(trim((string) $request->input('payment_method', '')));
        $repatriationStatus = strtolower((string) ($fxCompliancePayload['repatriation_status'] ?? 'not_applicable'));

        if ($currencyCode !== 'MZN') {
            $riskFlags[] = 'foreign_currency';
        }

        if ((bool) ($fxCompliancePayload['is_export_receipt'] ?? false)) {
            $riskFlags[] = 'export_receipt';
        }

        if (in_array($repatriationStatus, ['pending', 'partial'], true)) {
            $riskFlags[] = 'repatriation_pending';
        }

        if ((bool) ($gifimPayload['gifim_alert_required'] ?? false)) {
            $riskFlags[] = 'gifim_threshold';
        }

        $cashThreshold = (float) config('sce.gifim.cash_threshold_mzn', 250000);
        $electronicThreshold = (float) config('sce.gifim.electronic_threshold_mzn', 750000);

        if ($paymentMethod === 'cash' && $amountMzn >= $cashThreshold) {
            $riskFlags[] = 'high_value_cash';
        }

        if ($paymentMethod !== 'cash' && $amountMzn >= $electronicThreshold) {
            $riskFlags[] = 'high_value_electronic';
        }

        $riskFlags = array_values(array_unique($riskFlags));
        $approvalRequired = count($riskFlags) > 0;

        return [
            'approval_required' => $approvalRequired,
            'approval_status' => $approvalRequired ? 'pending' : 'not_required',
            'approval_risk_flags' => $riskFlags,
            'approval_requested_at' => $approvalRequired ? now() : null,
        ];
    }

    private function resolveGifimCompliancePayload(StoreCustomerPaymentRequest $request, float $amountMzn): array
    {
        $thresholdCategory = $this->resolveGifimThresholdCategory(
            (string) $request->input('payment_method', ''),
            $amountMzn
        );

        $isAlertRequired = $thresholdCategory !== null;
        $gifimStatus = strtolower(trim((string) $request->input('gifim_alert_status', '')));
        if (!in_array($gifimStatus, ['not_required', 'pending', 'communicated'], true)) {
            $gifimStatus = '';
        }

        if ($isAlertRequired && ($gifimStatus === '' || $gifimStatus === 'not_required')) {
            $gifimStatus = 'pending';
        }
        if (!$isAlertRequired) {
            $gifimStatus = 'not_required';
        }

        $reportedAt = null;
        $reportedBy = null;
        if ($gifimStatus === 'communicated') {
            $reportedAt = $request->filled('gifim_reported_at')
                ? $request->input('gifim_reported_at')
                : now()->toDateTimeString();
            $reportedBy = Auth::id();
        }

        return [
            'gifim_alert_required' => $isAlertRequired,
            'gifim_alert_category' => $thresholdCategory,
            'gifim_alert_status' => $gifimStatus,
            'gifim_reference' => $request->filled('gifim_reference')
                ? trim((string) $request->input('gifim_reference'))
                : null,
            'gifim_reported_at' => $reportedAt,
            'gifim_reported_by' => $reportedBy,
            'gifim_submitted_document' => $request->filled('gifim_submitted_document')
                ? trim((string) $request->input('gifim_submitted_document'))
                : null,
            'gifim_justification' => $request->filled('gifim_justification')
                ? trim((string) $request->input('gifim_justification'))
                : null,
            'high_value_approval_reference' => $request->filled('high_value_approval_reference')
                ? trim((string) $request->input('high_value_approval_reference'))
                : null,
        ];
    }

    private function resolveGifimThresholdCategory(string $paymentMethod, float $amountMzn): ?string
    {
        $paymentMethod = strtolower(trim($paymentMethod));
        $cashThreshold = (float) config('sce.gifim.cash_threshold_mzn', 250000);
        $electronicThreshold = (float) config('sce.gifim.electronic_threshold_mzn', 750000);
        $electronicMethods = (array) config('sce.gifim.electronic_payment_methods', ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other']);

        if ($paymentMethod === 'cash' && $amountMzn >= $cashThreshold) {
            return 'cash_threshold';
        }

        if (in_array($paymentMethod, $electronicMethods, true) && $amountMzn >= $electronicThreshold) {
            return 'electronic_threshold';
        }

        return null;
    }
}
