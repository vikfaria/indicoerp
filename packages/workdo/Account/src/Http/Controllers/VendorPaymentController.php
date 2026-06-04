<?php

namespace Workdo\Account\Http\Controllers;

use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTransaction;
use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Models\VendorPaymentAllocation;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\DebitNoteApplication;
use Workdo\Account\Models\Vendor as VendorProfile;
use Workdo\Account\Http\Requests\StoreVendorPaymentRequest;
use Workdo\Account\Services\JournalService;
use Workdo\Account\Services\BankTransactionsService;
use App\Models\User;
use App\Models\PurchaseInvoice;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Workdo\Account\Events\CreateVendorPayment;
use Workdo\Account\Events\UpdateVendorPaymentStatus;
use Workdo\Account\Events\DestroyVendorPayment;
use Workdo\Account\Models\ExchangeControlDossier;
use Workdo\Account\Services\AccountCacheService;
use Workdo\Hrm\Models\Branch;

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
            $query = VendorPayment::with(['vendor', 'bankAccount.branch', 'branch', 'allocations.invoice', 'debitNoteApplications.debitNote'])
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
            $vendorProfiles = VendorProfile::query()
                ->where('created_by', creatorId())
                ->get()
                ->keyBy('user_id');

            $vendors = User::where('type', 'vendor')
                ->where('created_by', creatorId())
                ->get()
                ->map(function (User $vendorUser) use ($vendorProfiles): array {
                    $profile = $vendorProfiles->get($vendorUser->id);

                    return [
                        'id' => $vendorUser->id,
                        'name' => $profile?->company_name ?: $vendorUser->name,
                        'email' => $vendorUser->email,
                        'fiscal_residency_status' => $profile?->fiscal_residency_status ?? 'resident',
                        'fiscal_country' => $profile?->fiscal_country,
                        'withholding_tax_applicable' => (bool) ($profile?->withholding_tax_applicable ?? false),
                        'adt_eligible' => (bool) ($profile?->adt_eligible ?? false),
                        'adt_country' => $profile?->adt_country,
                    ];
                })
                ->values();

            $bankAccounts = BankAccount::where('is_active', true)
                ->where('created_by', creatorId())
                ->with('branch')
                ->get();

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
                $fxPayload = $this->resolveFxPayload($request);
                $internationalCompliancePayload = $this->resolveInternationalCompliancePayload($request);
                $gifimPayload = $this->resolveGifimCompliancePayload($request, (float) $fxPayload['amount_mzn']);
                $approvalPayload = $this->resolveApprovalWorkflowPayload($request, $fxPayload, $internationalCompliancePayload, $gifimPayload);

                $payment = new VendorPayment();
                $payment->payment_date = $request->payment_date;
                $payment->vendor_id = $request->vendor_id;
                $payment->payment_purpose = $request->input('payment_purpose', 'settlement');
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
                $payment->is_international_payment = $internationalCompliancePayload['is_international_payment'];
                $payment->beneficiary_country = $internationalCompliancePayload['beneficiary_country'];
                $payment->service_type = $internationalCompliancePayload['service_type'];
                $payment->withholding_tax_treatment = $internationalCompliancePayload['withholding_tax_treatment'];
                $payment->withholding_tax_rate = $internationalCompliancePayload['withholding_tax_rate'];
                $payment->withholding_tax_amount = $internationalCompliancePayload['withholding_tax_amount'];
                $payment->withholding_exemption_reason = $internationalCompliancePayload['withholding_exemption_reason'];
                $payment->adt_certificate_reference = $internationalCompliancePayload['adt_certificate_reference'];
                $payment->fiscal_compliance_reference = $internationalCompliancePayload['fiscal_compliance_reference'];
                $payment->financial_approval_reference = $internationalCompliancePayload['financial_approval_reference'];
                $payment->fx_authorization_reference = $internationalCompliancePayload['fx_authorization_reference'];
                $payment->contract_reference = $internationalCompliancePayload['contract_reference'];
                $payment->invoice_reference = $internationalCompliancePayload['invoice_reference'];
                $payment->bank_settlement_reference = $internationalCompliancePayload['bank_settlement_reference'];
                $payment->withholding_receipt_reference = $internationalCompliancePayload['withholding_receipt_reference'];
                $payment->correspondence_reference = $internationalCompliancePayload['correspondence_reference'];
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

                $this->syncWithholdingFromVendorPayment($payment);
                $this->syncExchangeControlDossierFromVendorPayment($payment, $request);

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

                if (
                    $request->status === 'cleared'
                    && $vendorPayment->approval_required
                    && $vendorPayment->approval_status !== 'approved'
                ) {
                    return back()->with('error', __('Payment must be approved before it can be marked as cleared.'));
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

                    if ($request->status === 'cancelled') {
                        $this->deleteWithholdingForVendorPayment($vendorPayment);
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

    public function applyAdvance(Request $request, VendorPayment $vendorPayment)
    {
        if (!Auth::user()->can('cleared-vendor-payments') || $vendorPayment->created_by !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        if (($vendorPayment->payment_purpose ?? 'settlement') !== 'advance') {
            return back()->with('error', __('Only vendor advance payments can be applied to invoices.'));
        }

        if ($vendorPayment->status !== 'cleared') {
            return back()->with('error', __('The vendor advance payment must be cleared before it can be applied.'));
        }

        $validated = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => [
                'required',
                Rule::exists('purchase_invoices', 'id')->where(function ($query) use ($vendorPayment) {
                    $query->where('created_by', creatorId())
                        ->where('vendor_id', $vendorPayment->vendor_id)
                        ->whereIn('status', ['posted', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            DB::transaction(function () use ($request, $vendorPayment, $validated): void {
                $vendorPayment->loadMissing('vendor', 'allocations.invoice');

                if ($vendorPayment->allocations()->exists()) {
                    throw new \Exception(__('This vendor advance has already been applied to invoices.'));
                }

                $totalAllocated = 0;

                foreach ($validated['allocations'] as $allocation) {
                    $invoice = PurchaseInvoice::query()
                        ->where('id', $allocation['invoice_id'])
                        ->where('vendor_id', $vendorPayment->vendor_id)
                        ->where('created_by', creatorId())
                        ->whereIn('status', ['posted', 'partial'])
                        ->where('balance_amount', '>', 0)
                        ->firstOrFail();

                    $amount = round((float) $allocation['amount'], 2);
                    if ($amount > (float) $invoice->balance_amount + 0.0001) {
                        throw new \Exception(__('Allocation amount cannot exceed the invoice balance.'));
                    }

                    VendorPaymentAllocation::query()->create([
                        'payment_id' => $vendorPayment->id,
                        'invoice_id' => $invoice->id,
                        'allocated_amount' => $amount,
                        'creator_id' => Auth::id(),
                        'created_by' => creatorId(),
                    ]);

                    $invoice->paid_amount = round((float) $invoice->paid_amount + $amount, 2);
                    $invoice->balance_amount = round(max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount), 2);
                    $invoice->status = $invoice->balance_amount <= 0.01
                        ? 'paid'
                        : ((float) $invoice->paid_amount > 0 ? 'partial' : $invoice->status);
                    $invoice->save();

                    $totalAllocated += $amount;
                }

                if ($totalAllocated <= 0) {
                    throw new \Exception(__('At least one invoice allocation is required to apply a vendor advance.'));
                }

                if (abs($totalAllocated - (float) $vendorPayment->payment_amount) > 0.01) {
                    throw new \Exception(__('The allocated amount must match the vendor advance amount exactly.'));
                }

                $vendorPayment->unsetRelation('allocations');
                $this->journalService->createVendorAdvanceSettlementJournal($vendorPayment);
            });

            return back()->with('success', __('The vendor advance has been applied to invoices successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approve(Request $request, VendorPayment $vendorPayment)
    {
        if (!Auth::user()->can('approve-vendor-payments') || $vendorPayment->created_by !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $request->validate([
            'approval_reference' => 'nullable|string|max:120',
        ]);

        if ($vendorPayment->status !== 'pending') {
            return back()->with('error', __('Only pending vendor payments can be approved.'));
        }

        if (!$vendorPayment->approval_required) {
            return back()->with('error', __('This payment does not require approval.'));
        }

        DB::transaction(function () use ($request, $vendorPayment): void {
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
            } elseif (empty($vendorPayment->approval_reference) && !empty($vendorPayment->financial_approval_reference)) {
                $payload['approval_reference'] = $vendorPayment->financial_approval_reference;
            }

            $vendorPayment->update($payload);

            if (
                empty($vendorPayment->financial_approval_reference)
                && !empty($payload['approval_reference'])
            ) {
                $vendorPayment->update([
                    'financial_approval_reference' => $payload['approval_reference'],
                ]);
            }
        });

        return back()->with('success', __('Payment approved successfully.'));
    }

    public function reject(Request $request, VendorPayment $vendorPayment)
    {
        if (!Auth::user()->can('approve-vendor-payments') || $vendorPayment->created_by !== creatorId()) {
            return back()->with('error', __('Permission denied'));
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        if ($vendorPayment->status !== 'pending') {
            return back()->with('error', __('Only pending vendor payments can be rejected.'));
        }

        if (!$vendorPayment->approval_required) {
            return back()->with('error', __('This payment does not require approval.'));
        }

        if ($vendorPayment->approval_status === 'approved') {
            return back()->with('error', __('Approved payments cannot be rejected.'));
        }

        $vendorPayment->update([
            'approval_status' => 'rejected',
            'rejection_reason' => trim((string) $request->input('rejection_reason')),
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', __('Payment rejected successfully.'));
    }

    public function destroy(VendorPayment $vendorPayment)
    {
        if(Auth::user()->can('delete-vendor-payments') && $vendorPayment->created_by == creatorId() && $vendorPayment->status === 'pending'){

            // Dispatch event before deletion
            DestroyVendorPayment::dispatch($vendorPayment);

            $this->deleteWithholdingForVendorPayment($vendorPayment);
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

    private function resolveFxPayload(StoreVendorPaymentRequest $request): array
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

    private function resolveInternationalCompliancePayload(StoreVendorPaymentRequest $request): array
    {
        $currencyCode = strtoupper((string) $request->input('currency_code', 'MZN'));
        $vendorProfile = VendorProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $request->input('vendor_id'))
            ->first();

        $isVendorNonResident = strtolower((string) ($vendorProfile?->fiscal_residency_status ?? 'resident')) === 'non_resident';
        $isInternationalPayment = $request->boolean('is_international_payment')
            || $currencyCode !== 'MZN'
            || $isVendorNonResident;

        if (!$isInternationalPayment) {
            return [
                'is_international_payment' => false,
                'beneficiary_country' => null,
                'service_type' => null,
                'withholding_tax_treatment' => null,
                'withholding_tax_rate' => null,
                'withholding_tax_amount' => null,
                'withholding_exemption_reason' => null,
                'adt_certificate_reference' => null,
                'fiscal_compliance_reference' => null,
                'financial_approval_reference' => null,
                'fx_authorization_reference' => null,
                'contract_reference' => null,
                'invoice_reference' => null,
                'bank_settlement_reference' => null,
                'withholding_receipt_reference' => null,
                'correspondence_reference' => null,
            ];
        }

        return [
            'is_international_payment' => true,
            'beneficiary_country' => trim((string) $request->input('beneficiary_country')),
            'service_type' => trim((string) $request->input('service_type')),
            'withholding_tax_treatment' => $request->input('withholding_tax_treatment'),
            'withholding_tax_rate' => $request->filled('withholding_tax_rate')
                ? round((float) $request->input('withholding_tax_rate'), 4)
                : null,
            'withholding_tax_amount' => $request->filled('withholding_tax_amount')
                ? round((float) $request->input('withholding_tax_amount'), 2)
                : null,
            'withholding_exemption_reason' => $request->filled('withholding_exemption_reason')
                ? trim((string) $request->input('withholding_exemption_reason'))
                : null,
            'adt_certificate_reference' => $request->filled('adt_certificate_reference')
                ? trim((string) $request->input('adt_certificate_reference'))
                : null,
            'fiscal_compliance_reference' => trim((string) $request->input('fiscal_compliance_reference')),
            'financial_approval_reference' => trim((string) $request->input('financial_approval_reference')),
            'fx_authorization_reference' => trim((string) $request->input('fx_authorization_reference')),
            'contract_reference' => $request->filled('contract_reference')
                ? trim((string) $request->input('contract_reference'))
                : null,
            'invoice_reference' => $request->filled('invoice_reference')
                ? trim((string) $request->input('invoice_reference'))
                : null,
            'bank_settlement_reference' => $request->filled('bank_settlement_reference')
                ? trim((string) $request->input('bank_settlement_reference'))
                : null,
            'withholding_receipt_reference' => $request->filled('withholding_receipt_reference')
                ? trim((string) $request->input('withholding_receipt_reference'))
                : null,
            'correspondence_reference' => $request->filled('correspondence_reference')
                ? trim((string) $request->input('correspondence_reference'))
                : null,
        ];
    }

    private function resolveGifimCompliancePayload(StoreVendorPaymentRequest $request, float $amountMzn): array
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

    private function resolveApprovalWorkflowPayload(
        StoreVendorPaymentRequest $request,
        array $fxPayload,
        array $internationalCompliancePayload,
        array $gifimPayload
    ): array {
        $riskFlags = [];
        $currencyCode = strtoupper((string) ($fxPayload['currency_code'] ?? 'MZN'));
        $amountMzn = (float) ($fxPayload['amount_mzn'] ?? 0);
        $paymentMethod = strtolower(trim((string) $request->input('payment_method', '')));
        $beneficiaryCountry = trim((string) ($internationalCompliancePayload['beneficiary_country'] ?? ''));

        if ($currencyCode !== 'MZN') {
            $riskFlags[] = 'foreign_currency';
        }

        if ((bool) ($internationalCompliancePayload['is_international_payment'] ?? false)) {
            $riskFlags[] = 'international_payment';
        }

        if (in_array((string) ($internationalCompliancePayload['withholding_tax_treatment'] ?? ''), ['withheld', 'adt_reduced'], true)) {
            $riskFlags[] = 'withholding_tax_operation';
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

        if ($beneficiaryCountry !== '' && !$this->isMozambiqueCountry($beneficiaryCountry)) {
            $riskFlags[] = 'cross_border_beneficiary';
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

    private function syncWithholdingFromVendorPayment(VendorPayment $payment): void
    {
        if (!Schema::hasTable('withholding_tax_transactions')) {
            return;
        }

        if (!in_array((string) $payment->withholding_tax_treatment, ['withheld', 'adt_reduced'], true)) {
            return;
        }

        $rule = $this->resolveWithholdingRuleForPayment($payment);
        if (!$rule) {
            return;
        }

        $grossAmount = round((float) ($payment->amount_mzn ?? $payment->payment_amount), 2);
        $withholdingAmount = round((float) ($payment->withholding_tax_amount ?? 0), 2);
        if ($grossAmount <= 0 || $withholdingAmount <= 0) {
            return;
        }

        $vendorProfile = VendorProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $payment->vendor_id)
            ->first();

        $vendorNuit = $this->normalizeNuit($vendorProfile?->tax_number);
        $vendorName = $vendorProfile?->company_name ?: optional($payment->vendor)->name;
        $beneficiaryCountry = $payment->beneficiary_country ?: $vendorProfile?->fiscal_country;
        $residencyStatus = strtolower((string) ($vendorProfile?->fiscal_residency_status ?? 'resident')) === 'non_resident'
            ? 'non_resident'
            : 'resident';
        $transactionDate = $payment->payment_date?->toDateString() ?: now()->toDateString();
        $rate = round((float) ($payment->withholding_tax_rate ?? $rule->rate), 2);
        $netAmount = round($grossAmount - $withholdingAmount, 2);
        $incomeTypeSnapshot = (string) ($rule->income_type ?: $this->mapServiceTypeToIncomeType((string) $payment->service_type));
        $hasSourceColumns = Schema::hasColumn('withholding_tax_transactions', 'source_reference_type')
            && Schema::hasColumn('withholding_tax_transactions', 'source_reference_id');

        $uniqueBy = [
            'company_id' => creatorId(),
            'document_reference' => (string) $payment->payment_number,
            'vendor_id' => $payment->vendor_id,
            'withholding_rule_id' => $rule->id,
        ];
        if ($hasSourceColumns) {
            $uniqueBy['source_reference_type'] = 'vendor_payment';
            $uniqueBy['source_reference_id'] = $payment->id;
        }

        $payload = [
            'vendor_nuit' => $vendorNuit,
            'vendor_name' => $vendorName,
            'transaction_date' => $transactionDate,
            'gross_amount' => $grossAmount,
            'withholding_rate' => $rate,
            'withholding_amount' => $withholdingAmount,
            'net_amount' => $netAmount,
            'fiscal_year' => date('Y', strtotime($transactionDate)),
            'fiscal_month' => (int) date('m', strtotime($transactionDate)),
            'status' => 'pending',
            'created_by' => creatorId(),
        ];

        if (Schema::hasColumn('withholding_tax_transactions', 'beneficiary_country')) {
            $payload['beneficiary_country'] = $beneficiaryCountry;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'beneficiary_residency_status')) {
            $payload['beneficiary_residency_status'] = $residencyStatus;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'income_type_snapshot')) {
            $payload['income_type_snapshot'] = $incomeTypeSnapshot;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'withholding_treatment')) {
            $payload['withholding_treatment'] = $payment->withholding_tax_treatment;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'adt_applied')) {
            $payload['adt_applied'] = $payment->withholding_tax_treatment === 'adt_reduced';
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'adt_certificate_reference')) {
            $payload['adt_certificate_reference'] = $payment->adt_certificate_reference;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'fiscal_compliance_reference')) {
            $payload['fiscal_compliance_reference'] = $payment->fiscal_compliance_reference;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'financial_approval_reference')) {
            $payload['financial_approval_reference'] = $payment->financial_approval_reference;
        }
        if (Schema::hasColumn('withholding_tax_transactions', 'fx_authorization_reference')) {
            $payload['fx_authorization_reference'] = $payment->fx_authorization_reference;
        }

        WithholdingTaxTransaction::updateOrCreate($uniqueBy, $payload);
    }

    private function deleteWithholdingForVendorPayment(VendorPayment $payment): void
    {
        if (!Schema::hasTable('withholding_tax_transactions')) {
            return;
        }

        $query = WithholdingTaxTransaction::query()
            ->where('company_id', creatorId())
            ->where('vendor_id', $payment->vendor_id);

        if (
            Schema::hasColumn('withholding_tax_transactions', 'source_reference_type')
            && Schema::hasColumn('withholding_tax_transactions', 'source_reference_id')
        ) {
            $query->where('source_reference_type', 'vendor_payment')
                ->where('source_reference_id', $payment->id);
        } else {
            $query->where('document_reference', (string) $payment->payment_number);
        }

        $query->delete();
    }

    private function syncExchangeControlDossierFromVendorPayment(VendorPayment $payment, StoreVendorPaymentRequest $request): void
    {
        if (
            !Schema::hasTable('exchange_control_dossiers')
            || !((bool) $payment->is_international_payment || strtoupper((string) $payment->currency_code) !== 'MZN')
        ) {
            return;
        }

        $existingDossier = ExchangeControlDossier::query()->firstOrNew([
            'company_id' => creatorId(),
            'direction' => 'outbound',
            'payment_type' => 'vendor_payment',
            'payment_id' => $payment->id,
        ]);

        $documents = is_array($existingDossier->documents) ? $existingDossier->documents : [];
        $derivedInvoiceReference = $payment->allocations()
            ->with('invoice:id,invoice_number')
            ->first()
            ?->invoice
            ?->invoice_number;
        $withholdingTreatment = strtolower((string) ($payment->withholding_tax_treatment ?? ''));

        $incomingDocuments = [
            'contract_reference' => trim((string) ($payment->contract_reference ?? $request->input('contract_reference', ''))),
            'invoice_reference' => trim((string) ($payment->invoice_reference ?? $request->input('invoice_reference', ''))) ?: trim((string) ($derivedInvoiceReference ?? '')),
            'bank_settlement_reference' => trim((string) ($payment->bank_settlement_reference ?? $request->input('bank_settlement_reference', ''))) ?: trim((string) ($payment->reference_number ?? '')),
            'withholding_receipt_reference' => trim((string) ($payment->withholding_receipt_reference ?? $request->input('withholding_receipt_reference', ''))),
            'fx_authorization_reference' => trim((string) ($payment->fx_authorization_reference ?? '')),
            'correspondence_reference' => trim((string) ($payment->correspondence_reference ?? $request->input('correspondence_reference', ''))),
        ];

        if (
            $incomingDocuments['withholding_receipt_reference'] === ''
            && in_array($withholdingTreatment, ['withheld', 'adt_reduced'], true)
        ) {
            $incomingDocuments['withholding_receipt_reference'] = trim((string) ($payment->fiscal_compliance_reference ?? ''));
        }

        foreach ($incomingDocuments as $key => $value) {
            if ($value !== '' || !array_key_exists($key, $documents)) {
                $documents[$key] = $value;
            }
        }

        $required = $this->resolveExchangeDossierRequirementsForVendorPayment($payment);
        $missing = array_values(array_filter($required, static function (string $field) use ($documents): bool {
            return trim((string) ($documents[$field] ?? '')) === '';
        }));
        $isComplete = $missing === [];

        $existingDossier->payment_reference = (string) ($payment->payment_number ?: $payment->reference_number ?: $payment->id);
        $existingDossier->operation_date = $payment->payment_date;
        $existingDossier->counterparty_name = optional($payment->vendor)->name;
        $existingDossier->counterparty_country = (string) ($payment->beneficiary_country ?? '');
        $existingDossier->currency_code = (string) ($payment->currency_code ?? 'MZN');
        $existingDossier->amount_mzn = round((float) ($payment->amount_mzn ?? $payment->payment_amount ?? 0), 2);
        $existingDossier->documents = $documents;
        $existingDossier->required_documents = $required;
        $existingDossier->missing_documents = $missing;
        $existingDossier->is_complete = $isComplete;
        $existingDossier->completed_at = $isComplete ? now() : null;
        $existingDossier->completed_by = $isComplete ? Auth::id() : null;
        if (!$existingDossier->exists) {
            $existingDossier->created_by = creatorId();
        }
        $existingDossier->updated_by = Auth::id();
        $existingDossier->save();

        AccountCacheService::bumpForCompany((int) creatorId());
    }

    /**
     * @return array<int, string>
     */
    private function resolveExchangeDossierRequirementsForVendorPayment(VendorPayment $payment): array
    {
        $required = [
            'contract_reference',
            'invoice_reference',
            'bank_settlement_reference',
            'fx_authorization_reference',
        ];

        if (in_array(strtolower((string) ($payment->withholding_tax_treatment ?? '')), ['withheld', 'adt_reduced'], true)) {
            $required[] = 'withholding_receipt_reference';
        }

        return array_values(array_unique($required));
    }

    private function resolveWithholdingRuleForPayment(VendorPayment $payment): ?WithholdingTaxRule
    {
        $vendorProfile = VendorProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $payment->vendor_id)
            ->first();

        $appliesTo = strtolower((string) ($vendorProfile?->fiscal_residency_status ?? 'resident')) === 'non_resident'
            ? 'non_resident'
            : 'resident';

        $incomeType = $this->mapServiceTypeToIncomeType((string) $payment->service_type);

        return WithholdingTaxRule::query()
            ->where('is_active', true)
            ->where('income_type', $incomeType)
            ->whereIn('applies_to', [$appliesTo, 'both'])
            ->orderByRaw("CASE WHEN applies_to = ? THEN 0 ELSE 1 END", [$appliesTo])
            ->first();
    }

    private function mapServiceTypeToIncomeType(string $serviceType): string
    {
        return match ($serviceType) {
            'consulting' => 'services',
            'digital_services' => 'technical_assistance',
            'licensing' => 'royalties',
            'goods_import' => 'other',
            default => 'other',
        };
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

    private function normalizeNuit(?string $taxNumber): ?string
    {
        $digitsOnly = preg_replace('/\D+/', '', (string) ($taxNumber ?? ''));

        if ($digitsOnly === null || strlen($digitsOnly) !== 9) {
            return null;
        }

        return $digitsOnly;
    }

    private function isMozambiqueCountry(string $country): bool
    {
        $normalized = strtolower(trim($country));
        return in_array($normalized, ['mozambique', 'moçambique', 'mz'], true);
    }
}
