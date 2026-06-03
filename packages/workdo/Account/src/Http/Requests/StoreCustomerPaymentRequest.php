<?php

namespace Workdo\Account\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Permission;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\CreditNote;
use Workdo\Account\Models\Customer as CustomerProfile;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\VendorPayment;

class StoreCustomerPaymentRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    protected function prepareForValidation(): void
    {
        if (!$this->filled('payment_method')) {
            $this->merge([
                'payment_method' => 'bank_transfer',
            ]);
        }

        $currencyCode = strtoupper((string) $this->input('currency_code', 'MZN'));
        if ($currencyCode === '') {
            $currencyCode = 'MZN';
        }

        $this->merge([
            'currency_code' => $currencyCode,
            'is_export_receipt' => $this->boolean('is_export_receipt'),
        ]);

        if (!$this->filled('repatriation_status')) {
            $this->merge([
                'repatriation_status' => $this->boolean('is_export_receipt')
                    ? 'pending'
                    : 'not_applicable',
            ]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $customerId = (int) $this->input('customer_id');
        $paymentMethods = ['bank_transfer', 'cash', 'cheque', 'card', 'mobile_money', 'other'];
        $mobileMoneyProviders = ['mpesa', 'emola', 'mkesh'];

        return [
            'payment_date' => 'required|date|before_or_equal:today',
            'customer_id' => ['required', $this->companyClientExistsRule()],
            'bank_account_id' => ['required', $this->tenantOwnedExistsRule('bank_accounts', 'id', ['is_active' => true])],
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'mobile_money_provider' => ['nullable', 'required_if:payment_method,mobile_money', Rule::in($mobileMoneyProviders)],
            'mobile_money_number' => 'nullable|required_if:payment_method,mobile_money|string|max:30|regex:/^\+?[0-9]{8,15}$/',
            'reference_number' => 'nullable|string|max:100',
            'payment_amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'foreign_amount' => 'nullable|numeric|min:0.01',
            'is_export_receipt' => 'nullable|boolean',
            'receipt_origin_country' => 'nullable|string|max:120',
            'export_reference' => 'nullable|string|max:120',
            'intermediary_bank' => 'nullable|string|max:120',
            'repatriation_status' => ['nullable', Rule::in(['not_applicable', 'pending', 'partial', 'completed'])],
            'repatriated_amount_mzn' => 'nullable|numeric|min:0',
            'fx_compliance_reference' => 'nullable|string|max:120',
            'gifim_alert_category' => ['nullable', Rule::in(['cash_threshold', 'electronic_threshold'])],
            'gifim_alert_status' => ['nullable', Rule::in(['not_required', 'pending', 'communicated'])],
            'gifim_reference' => 'nullable|string|max:120',
            'gifim_reported_at' => 'nullable|date',
            'gifim_submitted_document' => 'nullable|string|max:255',
            'gifim_justification' => 'nullable|string|max:255',
            'high_value_approval_reference' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
            'allocations' => 'nullable|array',
            'allocations.*.invoice_id' => [
                'required',
                Rule::exists('sales_invoices', 'id')->where(function ($query) use ($customerId) {
                    $query->where('created_by', creatorId())
                        ->where('customer_id', $customerId)
                        ->whereIn('status', ['posted', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'credit_notes' => 'nullable|array',
            'credit_notes.*.credit_note_id' => [
                'required',
                Rule::exists('credit_notes', 'id')->where(function ($query) use ($customerId) {
                    $query->where('created_by', creatorId())
                        ->where('customer_id', $customerId)
                        ->whereIn('status', ['approved', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'credit_notes.*.amount' => 'required|numeric|min:0.01',
        ];
    }

    public function messages()
    {
        return [
            'payment_date.before_or_equal' => __('Payment date cannot be in the future.'),
            'payment_method.required' => __('Payment method is required.'),
            'mobile_money_provider.required_if' => __('Mobile money provider is required when payment method is mobile money.'),
            'mobile_money_number.required_if' => __('Mobile money number is required when payment method is mobile money.'),
            'mobile_money_number.regex' => __('Mobile money number format is invalid.'),
            'allocations.*.amount.min' => __('Allocation amount must be greater than 0.'),
            'credit_notes.*.amount.min' => __('Credit note amount must be greater than 0.')
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allocations = collect($this->input('allocations', []));
            $creditNotes = collect($this->input('credit_notes', []));

            if ($allocations->isEmpty()) {
                $validator->errors()->add('allocations', __('At least one invoice allocation is required to create a payment.'));
                return;
            }

            $invoiceBalances = SalesInvoice::query()
                ->where('created_by', creatorId())
                ->where('customer_id', $this->input('customer_id'))
                ->whereIn('status', ['posted', 'partial'])
                ->where('balance_amount', '>', 0)
                ->whereIn('id', $allocations->pluck('invoice_id')->filter()->all())
                ->get(['id', 'balance_amount'])
                ->keyBy('id');

            foreach ($allocations as $index => $allocation) {
                $invoice = $invoiceBalances->get((int) data_get($allocation, 'invoice_id'));
                $amount = (float) data_get($allocation, 'amount', 0);

                if ($invoice && $amount > (float) $invoice->balance_amount + 0.0001) {
                    $validator->errors()->add("allocations.$index.amount", __('Allocation amount cannot exceed the invoice balance.'));
                }
            }

            $creditNoteBalances = CreditNote::query()
                ->where('created_by', creatorId())
                ->where('customer_id', $this->input('customer_id'))
                ->whereIn('status', ['approved', 'partial'])
                ->where('balance_amount', '>', 0)
                ->whereIn('id', $creditNotes->pluck('credit_note_id')->filter()->all())
                ->get(['id', 'balance_amount'])
                ->keyBy('id');

            foreach ($creditNotes as $index => $creditNote) {
                $note = $creditNoteBalances->get((int) data_get($creditNote, 'credit_note_id'));
                $amount = (float) data_get($creditNote, 'amount', 0);

                if ($note && $amount > (float) $note->balance_amount + 0.0001) {
                    $validator->errors()->add("credit_notes.$index.amount", __('Credit note amount cannot exceed the available balance.'));
                }
            }

            $totalInvoiceAmount = round($allocations->sum(fn ($allocation) => (float) data_get($allocation, 'amount', 0)), 2);
            $totalCreditNoteAmount = round($creditNotes->sum(fn ($creditNote) => (float) data_get($creditNote, 'amount', 0)), 2);

            if ($totalCreditNoteAmount > $totalInvoiceAmount + 0.0001) {
                $validator->errors()->add('credit_notes', __('Credit note amount cannot exceed the total invoice allocation amount.'));
            }

            $expectedPaymentAmount = round(max(0, $totalInvoiceAmount - $totalCreditNoteAmount), 2);
            $paymentAmount = round((float) $this->input('payment_amount', 0), 2);

            if (abs($paymentAmount - $expectedPaymentAmount) > 0.01) {
                $validator->errors()->add('payment_amount', __('Payment amount must match allocations minus applied credit notes.'));
            }

            $currencyCode = strtoupper((string) $this->input('currency_code', 'MZN'));
            $isForeignCurrency = $currencyCode !== 'MZN';

            if (
                $isForeignCurrency
                && $this->isPermissionProvisioned('create-foreign-currency-customer-payments')
                && !$this->userCanAnyPermission([
                    'create-foreign-currency-customer-payments',
                    'manage-any-customer-payments',
                ])
            ) {
                $validator->errors()->add(
                    'currency_code',
                    __('You are not authorized to create customer payments in foreign currency.')
                );
            }

            if ($isForeignCurrency) {
                $exchangeRate = (float) $this->input('exchange_rate', 0);
                $foreignAmount = (float) $this->input('foreign_amount', 0);

                if ($exchangeRate <= 0) {
                    $validator->errors()->add('exchange_rate', __('Exchange rate must be greater than zero for foreign currency payments.'));
                }

                if ($foreignAmount <= 0) {
                    $validator->errors()->add('foreign_amount', __('Foreign amount must be greater than zero for foreign currency payments.'));
                }

                if ($exchangeRate > 0 && $foreignAmount > 0) {
                    $convertedAmount = round($foreignAmount * $exchangeRate, 2);
                    $fxDifference = round($paymentAmount - $convertedAmount, 2);

                    if (abs($fxDifference) > 0.01 && trim((string) $this->input('notes', '')) === '') {
                        $validator->errors()->add(
                            'notes',
                            __('Provide notes when there is an exchange difference between foreign currency conversion and payment allocation amount.')
                        );
                    }
                }
            }

            $customerProfile = $this->resolveCustomerFiscalProfile();
            $isNonResidentCustomer = $this->isCustomerNonResident($customerProfile);
            $isExportReceipt = $this->boolean('is_export_receipt');

            if ($isForeignCurrency && !$isNonResidentCustomer && !$isExportReceipt) {
                $validator->errors()->add(
                    'currency_code',
                    __('Domestic operations must be settled in Meticais (MZN). Mark the payment as export receipt when FX control applies.')
                );
            }

            $requiresAuthorizedFinancialChannel = $isExportReceipt || $isForeignCurrency || $isNonResidentCustomer;
            if ($requiresAuthorizedFinancialChannel) {
                $paymentMethod = strtolower(trim((string) $this->input('payment_method', '')));
                if (!in_array($paymentMethod, ['bank_transfer', 'mobile_money'], true)) {
                    $validator->errors()->add(
                        'payment_method',
                        __('Cross-border receipts must be processed through authorized financial channels (bank transfer or mobile money).')
                    );
                }
            }

            if ($isExportReceipt) {
                if (trim((string) $this->input('receipt_origin_country', '')) === '') {
                    $validator->errors()->add('receipt_origin_country', __('Receipt origin country is required for export receipts.'));
                }

                if (trim((string) $this->input('export_reference', '')) === '') {
                    $validator->errors()->add('export_reference', __('Export reference is required for export receipts.'));
                }

                if (trim((string) $this->input('intermediary_bank', '')) === '') {
                    $validator->errors()->add('intermediary_bank', __('Intermediary bank is required for export receipts.'));
                }

                if (trim((string) $this->input('fx_compliance_reference', '')) === '') {
                    $validator->errors()->add('fx_compliance_reference', __('FX compliance reference is required for export receipts.'));
                }

                $repatriationStatus = (string) $this->input('repatriation_status', 'pending');
                $repatriatedAmount = (float) $this->input('repatriated_amount_mzn', 0);

                if (in_array($repatriationStatus, ['partial', 'completed'], true) && $repatriatedAmount <= 0) {
                    $validator->errors()->add('repatriated_amount_mzn', __('Repatriated amount must be greater than zero for partial/completed repatriation status.'));
                }

                if ($repatriationStatus === 'completed' && $repatriatedAmount + 0.01 < $paymentAmount) {
                    $validator->errors()->add('repatriated_amount_mzn', __('Completed repatriation must cover the payment amount in MZN.'));
                }
            }

            $gifimThresholdCategory = $this->resolveGifimThresholdCategory(
                (string) $this->input('payment_method', ''),
                $paymentAmount
            );

            if (
                $gifimThresholdCategory !== null
                && $this->isPermissionProvisioned('create-high-value-customer-payments')
                && !$this->userCanAnyPermission([
                    'create-high-value-customer-payments',
                    'approve-customer-payments',
                    'manage-any-customer-payments',
                ])
            ) {
                $validator->errors()->add(
                    'payment_amount',
                    __('You are not authorized to create high-value customer payments.')
                );
            }

            if ($gifimThresholdCategory !== null) {
                $gifimStatus = (string) $this->input('gifim_alert_status', 'pending');
                if ($gifimStatus === '' || $gifimStatus === 'not_required') {
                    $gifimStatus = 'pending';
                }

                if (trim((string) $this->input('high_value_approval_reference', '')) === '') {
                    $validator->errors()->add(
                        'high_value_approval_reference',
                        __('High-value payments must include an approval reference before completion.')
                    );
                }

                if ($gifimStatus === 'communicated') {
                    if (trim((string) $this->input('gifim_reference', '')) === '') {
                        $validator->errors()->add('gifim_reference', __('Provide GIFiM reference when communication status is communicated.'));
                    }

                    if (trim((string) $this->input('gifim_submitted_document', '')) === '') {
                        $validator->errors()->add('gifim_submitted_document', __('Provide submitted document reference when GIFiM communication is marked as communicated.'));
                    }
                }
            }

            $this->validateBankAccountAccess($validator, (int) $this->input('bank_account_id'));
            $this->validateCashAccountUsage(
                $validator,
                (int) $this->input('bank_account_id'),
                (string) $this->input('payment_method', '')
            );
            $this->validateElectronicMoneyMonthlyLimit(
                $validator,
                (int) $this->input('bank_account_id'),
                (string) $this->input('payment_method', ''),
                (float) $this->input('payment_amount', 0)
            );
        });
    }

    private function validateBankAccountAccess(Validator $validator, int $bankAccountId): void
    {
        if ($bankAccountId <= 0 || !$this->isPermissionProvisioned('use-all-bank-accounts-for-customer-payments')) {
            return;
        }

        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', creatorId())
            ->first();

        if (!$bankAccount) {
            return;
        }

        $ownerId = (int) ($bankAccount->creator_id ?? 0);
        $currentUserId = (int) Auth::id();

        if (
            $ownerId > 0
            && $currentUserId > 0
            && $ownerId !== $currentUserId
            && !$this->userCanAnyPermission([
                'use-all-bank-accounts-for-customer-payments',
                'manage-any-bank-accounts',
                'manage-any-customer-payments',
            ])
        ) {
            $validator->errors()->add(
                'bank_account_id',
                __('You are not authorized to use bank accounts created by other users for customer payments.')
            );
        }
    }

    private function validateCashAccountUsage(Validator $validator, int $bankAccountId, string $paymentMethod): void
    {
        if ($bankAccountId <= 0 || strtolower(trim($paymentMethod)) !== 'cash') {
            return;
        }

        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', creatorId())
            ->first();

        if (!$bankAccount) {
            return;
        }

        if (!$this->isCashAccountType((string) $bankAccount->account_type)) {
            $validator->errors()->add(
                'bank_account_id',
                __('Cash payments must use a cashbox or petty cash account.')
            );
        }
    }

    private function userCanAnyPermission(array $permissions): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function isPermissionProvisioned(string $permissionName): bool
    {
        return Permission::query()
            ->where('name', $permissionName)
            ->where('guard_name', 'web')
            ->exists();
    }

    private function resolveCustomerFiscalProfile(): ?CustomerProfile
    {
        return CustomerProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $this->input('customer_id'))
            ->first();
    }

    private function isCustomerNonResident(?CustomerProfile $customerProfile): bool
    {
        if (!$customerProfile) {
            return false;
        }

        return strtolower((string) $customerProfile->fiscal_residency_status) === 'non_resident';
    }

    private function isCashAccountType(string $accountType): bool
    {
        $normalized = strtolower(trim($accountType));

        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['cash', 'cashbox', 'petty_cash', 'petty-cash', 'cash_box', 'caixa', 'caixa_menor'], true)) {
            return true;
        }

        return str_contains($normalized, 'cash') || str_contains($normalized, 'caixa');
    }

    private function resolveGifimThresholdCategory(string $paymentMethod, float $amountMzn): ?string
    {
        $paymentMethod = strtolower(trim($paymentMethod));
        if ($paymentMethod === 'cash' && $amountMzn >= 250000) {
            return 'cash_threshold';
        }

        $electronicMethods = ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other'];
        if (in_array($paymentMethod, $electronicMethods, true) && $amountMzn >= 750000) {
            return 'electronic_threshold';
        }

        return null;
    }

    private function validateElectronicMoneyMonthlyLimit(
        Validator $validator,
        int $bankAccountId,
        string $paymentMethod,
        float $amountMzn
    ): void {
        if (
            $bankAccountId <= 0
            || strtolower(trim($paymentMethod)) !== 'mobile_money'
            || $amountMzn <= 0
        ) {
            return;
        }

        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', creatorId())
            ->first();

        if (!$bankAccount) {
            return;
        }

        if (!(bool) $bankAccount->is_electronic_money_account) {
            $validator->errors()->add(
                'bank_account_id',
                __('Mobile money operations must use a bank account configured as an electronic money account.')
            );
            return;
        }

        if (
            trim((string) ($bankAccount->electronic_money_entity ?? '')) === ''
            || trim((string) ($bankAccount->electronic_money_level ?? '')) === ''
        ) {
            $validator->errors()->add(
                'bank_account_id',
                __('Electronic money entity and account level must be configured before posting mobile money operations.')
            );
            return;
        }

        if (!$bankAccount->electronic_money_limit_exempt_for_enterprise) {
            $dailyLimit = (float) ($bankAccount->electronic_money_daily_limit_mzn ?? 0);
            $monthlyLimit = (float) ($bankAccount->electronic_money_monthly_limit_mzn ?? 0);

            if ($dailyLimit <= 0) {
                $validator->errors()->add(
                    'bank_account_id',
                    __('Electronic money daily limit must be configured for this account before posting mobile money operations.')
                );
                return;
            }

            if ($monthlyLimit <= 0) {
                $validator->errors()->add(
                    'bank_account_id',
                    __('Electronic money monthly limit must be configured for this account before posting mobile money operations.')
                );
                return;
            }

            $paymentDate = $this->filled('payment_date')
                ? Carbon::parse((string) $this->input('payment_date'))
                : now();
            $paymentDateString = $paymentDate->toDateString();
            $startOfMonth = $paymentDate->copy()->startOfMonth()->toDateString();
            $endOfMonth = $paymentDate->copy()->endOfMonth()->toDateString();

            $vendorDayTotal = (float) VendorPayment::query()
                ->where('created_by', creatorId())
                ->where('bank_account_id', $bankAccountId)
                ->where('payment_method', 'mobile_money')
                ->whereIn('status', ['pending', 'cleared'])
                ->whereDate('payment_date', $paymentDateString)
                ->sum(DB::raw('COALESCE(amount_mzn, payment_amount, 0)'));

            $customerDayTotal = (float) CustomerPayment::query()
                ->where('created_by', creatorId())
                ->where('bank_account_id', $bankAccountId)
                ->where('payment_method', 'mobile_money')
                ->whereIn('status', ['pending', 'cleared'])
                ->whereDate('payment_date', $paymentDateString)
                ->sum(DB::raw('COALESCE(amount_mzn, payment_amount, 0)'));

            $projectedDailyAmount = round($vendorDayTotal + $customerDayTotal + $amountMzn, 2);
            if ($projectedDailyAmount > $dailyLimit + 0.01) {
                $validator->errors()->add(
                    'payment_amount',
                    __('Mobile money operation exceeds the daily limit configured for this electronic money account.')
                );
            }

            $vendorMonthTotal = (float) VendorPayment::query()
                ->where('created_by', creatorId())
                ->where('bank_account_id', $bankAccountId)
                ->where('payment_method', 'mobile_money')
                ->whereIn('status', ['pending', 'cleared'])
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->sum(DB::raw('COALESCE(amount_mzn, payment_amount, 0)'));

            $customerMonthTotal = (float) CustomerPayment::query()
                ->where('created_by', creatorId())
                ->where('bank_account_id', $bankAccountId)
                ->where('payment_method', 'mobile_money')
                ->whereIn('status', ['pending', 'cleared'])
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->sum(DB::raw('COALESCE(amount_mzn, payment_amount, 0)'));

            $projectedAmount = round($vendorMonthTotal + $customerMonthTotal + $amountMzn, 2);
            if ($projectedAmount > $monthlyLimit + 0.01) {
                $validator->errors()->add(
                    'payment_amount',
                    __('Mobile money operation exceeds the monthly limit configured for this electronic money account.')
                );
            }
        }
    }
}
