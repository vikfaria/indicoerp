<?php

namespace Workdo\Account\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\PurchaseInvoice;
use App\Models\WithholdingTaxRule;
use App\Models\WithholdingTaxTreatyRate;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Permission;
use Workdo\Account\Models\BankAccount;
use Workdo\Account\Models\DebitNote;
use Workdo\Account\Models\Vendor as VendorProfile;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\VendorPayment;

class StoreVendorPaymentRequest extends FormRequest
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
            'is_international_payment' => $this->boolean('is_international_payment'),
        ]);
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $vendorId = (int) $this->input('vendor_id');
        $paymentMethods = ['bank_transfer', 'cash', 'cheque', 'card', 'mobile_money', 'other'];
        $mobileMoneyProviders = ['mpesa', 'emola', 'mkesh'];

        return [
            'payment_date' => 'required|date|before_or_equal:today',
            'vendor_id' => ['required', $this->companyVendorExistsRule()],
            'bank_account_id' => ['required', $this->tenantOwnedExistsRule('bank_accounts', 'id', ['is_active' => true])],
            'payment_method' => ['required', Rule::in($paymentMethods)],
            'mobile_money_provider' => ['nullable', 'required_if:payment_method,mobile_money', Rule::in($mobileMoneyProviders)],
            'mobile_money_number' => 'nullable|required_if:payment_method,mobile_money|string|max:30|regex:/^\+?[0-9]{8,15}$/',
            'reference_number' => 'nullable|string|max:100',
            'payment_amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0.000001',
            'foreign_amount' => 'nullable|numeric|min:0.01',
            'is_international_payment' => 'nullable|boolean',
            'beneficiary_country' => 'nullable|string|max:120',
            'service_type' => 'nullable|string|max:120',
            'withholding_tax_treatment' => ['nullable', Rule::in(['withheld', 'exempt', 'adt_reduced'])],
            'withholding_tax_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_tax_amount' => 'nullable|numeric|min:0',
            'withholding_exemption_reason' => 'nullable|string|max:255',
            'adt_certificate_reference' => 'nullable|string|max:120',
            'fiscal_compliance_reference' => 'nullable|string|max:120',
            'financial_approval_reference' => 'nullable|string|max:120',
            'fx_authorization_reference' => 'nullable|string|max:120',
            'contract_reference' => 'nullable|string|max:255',
            'invoice_reference' => 'nullable|string|max:255',
            'bank_settlement_reference' => 'nullable|string|max:255',
            'withholding_receipt_reference' => 'nullable|string|max:255',
            'correspondence_reference' => 'nullable|string|max:255',
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
                Rule::exists('purchase_invoices', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('created_by', creatorId())
                        ->where('vendor_id', $vendorId)
                        ->whereIn('status', ['posted', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'debit_notes' => 'nullable|array',
            'debit_notes.*.debit_note_id' => [
                'required',
                Rule::exists('debit_notes', 'id')->where(function ($query) use ($vendorId) {
                    $query->where('created_by', creatorId())
                        ->where('vendor_id', $vendorId)
                        ->whereIn('status', ['approved', 'partial'])
                        ->where('balance_amount', '>', 0);
                }),
            ],
            'debit_notes.*.amount' => 'required|numeric|min:0.01',
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
            'debit_notes.*.amount.min' => __('Debit note amount must be greater than 0.')
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allocations = collect($this->input('allocations', []));
            $debitNotes = collect($this->input('debit_notes', []));

            if ($allocations->isEmpty()) {
                $validator->errors()->add('allocations', __('At least one invoice allocation is required to create a payment.'));
                return;
            }

            $invoiceBalances = PurchaseInvoice::query()
                ->where('created_by', creatorId())
                ->where('vendor_id', $this->input('vendor_id'))
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

            $debitNoteBalances = DebitNote::query()
                ->where('created_by', creatorId())
                ->where('vendor_id', $this->input('vendor_id'))
                ->whereIn('status', ['approved', 'partial'])
                ->where('balance_amount', '>', 0)
                ->whereIn('id', $debitNotes->pluck('debit_note_id')->filter()->all())
                ->get(['id', 'balance_amount'])
                ->keyBy('id');

            foreach ($debitNotes as $index => $debitNote) {
                $note = $debitNoteBalances->get((int) data_get($debitNote, 'debit_note_id'));
                $amount = (float) data_get($debitNote, 'amount', 0);

                if ($note && $amount > (float) $note->balance_amount + 0.0001) {
                    $validator->errors()->add("debit_notes.$index.amount", __('Debit note amount cannot exceed the available balance.'));
                }
            }

            $totalInvoiceAmount = round($allocations->sum(fn ($allocation) => (float) data_get($allocation, 'amount', 0)), 2);
            $totalDebitNoteAmount = round($debitNotes->sum(fn ($debitNote) => (float) data_get($debitNote, 'amount', 0)), 2);

            if ($totalDebitNoteAmount > $totalInvoiceAmount + 0.0001) {
                $validator->errors()->add('debit_notes', __('Debit note amount cannot exceed the total invoice allocation amount.'));
            }

            $expectedPaymentAmount = round(max(0, $totalInvoiceAmount - $totalDebitNoteAmount), 2);
            $paymentAmount = round((float) $this->input('payment_amount', 0), 2);

            if (abs($paymentAmount - $expectedPaymentAmount) > 0.01) {
                $validator->errors()->add('payment_amount', __('Payment amount must match allocations minus applied debit notes.'));
            }

            $currencyCode = strtoupper((string) $this->input('currency_code', 'MZN'));
            $isForeignCurrencyPayment = $currencyCode !== 'MZN';

            if (
                $isForeignCurrencyPayment
                && $this->isPermissionProvisioned('create-foreign-currency-vendor-payments')
                && !$this->userCanAnyPermission([
                    'create-foreign-currency-vendor-payments',
                    'manage-any-vendor-payments',
                ])
            ) {
                $validator->errors()->add(
                    'currency_code',
                    __('You are not authorized to create vendor payments in foreign currency.')
                );
            }

            if ($currencyCode !== 'MZN') {
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

            $vendorProfile = $this->resolveVendorFiscalProfile();
            $isInternationalPayment = $this->boolean('is_international_payment')
                || $currencyCode !== 'MZN'
                || $this->isVendorNonResident($vendorProfile);
            $beneficiaryCountry = trim((string) $this->input('beneficiary_country', ''));

            if ($currencyCode !== 'MZN' && !$this->isVendorNonResident($vendorProfile)) {
                $countryForValidation = $beneficiaryCountry !== ''
                    ? $beneficiaryCountry
                    : (string) ($vendorProfile?->fiscal_country ?? '');

                if ($this->isMozambiqueCountry($countryForValidation) || $countryForValidation === '') {
                    $validator->errors()->add(
                        'currency_code',
                        __('Domestic operations must be settled in Meticais (MZN). Foreign currency remittance is only allowed for non-domestic beneficiaries.')
                    );
                }
            }

            if ($isInternationalPayment) {
                $paymentMethod = strtolower(trim((string) $this->input('payment_method', '')));
                if (!in_array($paymentMethod, ['bank_transfer', 'mobile_money'], true)) {
                    $validator->errors()->add(
                        'payment_method',
                        __('International remittances must be processed through authorized financial channels (bank transfer or mobile money).')
                    );
                }

                if ($beneficiaryCountry === '') {
                    $validator->errors()->add('beneficiary_country', __('Beneficiary country is required for international payments.'));
                }

                if (trim((string) $this->input('service_type', '')) === '') {
                    $validator->errors()->add('service_type', __('Service type is required for international payments.'));
                }

                $withholdingTaxTreatment = (string) $this->input('withholding_tax_treatment', '');
                if (!in_array($withholdingTaxTreatment, ['withheld', 'exempt', 'adt_reduced'], true)) {
                    $validator->errors()->add('withholding_tax_treatment', __('Select a withholding tax treatment for international payments.'));
                }

                if (in_array($withholdingTaxTreatment, ['withheld', 'adt_reduced'], true)) {
                    $withholdingTaxRate = (float) $this->input('withholding_tax_rate', 0);
                    $withholdingTaxAmount = (float) $this->input('withholding_tax_amount', 0);

                    if ($withholdingTaxRate <= 0) {
                        $validator->errors()->add('withholding_tax_rate', __('Withholding tax rate must be greater than zero for this treatment.'));
                    }

                    if ($withholdingTaxAmount <= 0) {
                        $validator->errors()->add('withholding_tax_amount', __('Withholding tax amount must be greater than zero for this treatment.'));
                    }
                }

                if ($withholdingTaxTreatment === 'exempt' && trim((string) $this->input('withholding_exemption_reason', '')) === '') {
                    $validator->errors()->add('withholding_exemption_reason', __('Provide the withholding exemption legal basis for international payments.'));
                }

                if ($withholdingTaxTreatment === 'adt_reduced') {
                    if (trim((string) $this->input('adt_certificate_reference', '')) === '') {
                        $validator->errors()->add('adt_certificate_reference', __('ADT certificate reference is required when reduced ADT withholding is used.'));
                    }

                    if (!$vendorProfile || !$vendorProfile->adt_eligible) {
                        $validator->errors()->add('withholding_tax_treatment', __('Vendor is not configured as ADT-eligible.'));
                    }
                }

                if (in_array($withholdingTaxTreatment, ['withheld', 'adt_reduced'], true)) {
                    $incomeType = $this->mapServiceTypeToIncomeType((string) $this->input('service_type'));
                    $appliesTo = $this->isVendorNonResident($vendorProfile) ? 'non_resident' : 'resident';

                    $withholdingRule = WithholdingTaxRule::query()
                        ->where('is_active', true)
                        ->where('income_type', $incomeType)
                        ->whereIn('applies_to', [$appliesTo, 'both'])
                        ->orderByRaw("CASE WHEN applies_to = ? THEN 0 ELSE 1 END", [$appliesTo])
                        ->first();

                    if (!$withholdingRule) {
                        $validator->errors()->add(
                            'withholding_tax_treatment',
                            __('No active withholding tax rule is configured for the selected service type and vendor residency.')
                        );
                    } elseif ($withholdingTaxTreatment === 'withheld') {
                        $inputRate = round((float) $this->input('withholding_tax_rate', 0), 4);
                        $ruleRate = round((float) $withholdingRule->rate, 4);

                        if (abs($inputRate - $ruleRate) > 0.0001) {
                            $validator->errors()->add(
                                'withholding_tax_rate',
                                __('Withholding tax rate must match the configured fiscal rule for this operation.')
                            );
                        }
                    } elseif ($withholdingTaxTreatment === 'adt_reduced') {
                        $countryForTreaty = $beneficiaryCountry !== ''
                            ? $beneficiaryCountry
                            : ((string) ($vendorProfile?->adt_country ?? '') !== ''
                                ? (string) $vendorProfile?->adt_country
                                : (string) ($vendorProfile?->fiscal_country ?? ''));

                        $treatyRate = $this->resolveApplicableTreatyRate($countryForTreaty, $incomeType);
                        if (!$treatyRate) {
                            $validator->errors()->add(
                                'withholding_tax_treatment',
                                __('No active ADT treaty rate is configured for :country and income type :income.', [
                                    'country' => $countryForTreaty !== '' ? $countryForTreaty : __('selected country'),
                                    'income' => $incomeType,
                                ])
                            );
                        } else {
                            $inputRate = round((float) $this->input('withholding_tax_rate', 0), 4);
                            $standardRate = round((float) $withholdingRule->rate, 4);
                            $configuredTreatyRate = round((float) $treatyRate->treaty_rate, 4);

                            if ($configuredTreatyRate > $standardRate + 0.0001) {
                                $validator->errors()->add(
                                    'withholding_tax_treatment',
                                    __('Treaty configuration is invalid because ADT rate (:treaty%) is above the standard local rate (:standard%).', [
                                        'treaty' => number_format($configuredTreatyRate, 4, '.', ''),
                                        'standard' => number_format($standardRate, 4, '.', ''),
                                    ])
                                );
                            }

                            if (abs($inputRate - $configuredTreatyRate) > 0.0001) {
                                $validator->errors()->add(
                                    'withholding_tax_rate',
                                    __('Withholding tax rate must match ADT treaty rate (:treaty%). Standard local rate is :standard%.', [
                                        'treaty' => number_format($configuredTreatyRate, 4, '.', ''),
                                        'standard' => number_format($standardRate, 4, '.', ''),
                                    ])
                                );
                            }

                            if (
                                (bool) $treatyRate->requires_residency_certificate
                                && !$this->vendorHasMatchingComplianceDocument(
                                    $vendorProfile,
                                    (string) $this->input('adt_certificate_reference', '')
                                )
                            ) {
                                $validator->errors()->add(
                                    'adt_certificate_reference',
                                    __('The ADT certificate reference must match a residency certificate or compliance document registered on the vendor profile.')
                                );
                            }
                        }
                    }
                }

                if (trim((string) $this->input('fiscal_compliance_reference', '')) === '') {
                    $validator->errors()->add('fiscal_compliance_reference', __('Fiscal compliance reference is required for international remittances.'));
                }

                if (trim((string) $this->input('financial_approval_reference', '')) === '') {
                    $validator->errors()->add('financial_approval_reference', __('Financial approval reference is required for international remittances.'));
                }

                if (trim((string) $this->input('fx_authorization_reference', '')) === '') {
                    $validator->errors()->add('fx_authorization_reference', __('FX authorization reference is required for international remittances.'));
                }

                if ($paymentMethod === 'bank_transfer') {
                    $this->validateInternationalBankTransferChannel(
                        $validator,
                        (int) $this->input('bank_account_id')
                    );
                }
            }

            $gifimThresholdCategory = $this->resolveGifimThresholdCategory(
                (string) $this->input('payment_method', ''),
                $paymentAmount
            );

            if (
                $gifimThresholdCategory !== null
                && $this->isPermissionProvisioned('create-high-value-vendor-payments')
                && !$this->userCanAnyPermission([
                    'create-high-value-vendor-payments',
                    'approve-vendor-payments',
                    'manage-any-vendor-payments',
                ])
            ) {
                $validator->errors()->add(
                    'payment_amount',
                    __('You are not authorized to create high-value vendor payments.')
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
        if ($bankAccountId <= 0 || !$this->isPermissionProvisioned('use-all-bank-accounts-for-vendor-payments')) {
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
                'use-all-bank-accounts-for-vendor-payments',
                'manage-any-bank-accounts',
                'manage-any-vendor-payments',
            ])
        ) {
            $validator->errors()->add(
                'bank_account_id',
                __('You are not authorized to use bank accounts created by other users for vendor payments.')
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

    private function resolveVendorFiscalProfile(): ?VendorProfile
    {
        return VendorProfile::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $this->input('vendor_id'))
            ->first();
    }

    private function isVendorNonResident(?VendorProfile $vendorProfile): bool
    {
        if (!$vendorProfile) {
            return false;
        }

        return strtolower((string) $vendorProfile->fiscal_residency_status) === 'non_resident';
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
        if ($paymentMethod === 'cash' && $amountMzn >= 250000) {
            return 'cash_threshold';
        }

        $electronicMethods = ['bank_transfer', 'cheque', 'card', 'mobile_money', 'other'];
        if (in_array($paymentMethod, $electronicMethods, true) && $amountMzn >= 750000) {
            return 'electronic_threshold';
        }

        return null;
    }

    private function isMozambiqueCountry(string $country): bool
    {
        $normalized = strtoupper(trim($country));
        $normalized = str_replace(['Á', 'À', 'Â', 'Ã', 'Ç', 'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú'], ['A', 'A', 'A', 'A', 'C', 'E', 'E', 'I', 'O', 'O', 'O', 'U'], $normalized);

        return in_array($normalized, ['MZ', 'MOZAMBIQUE', 'MOCAMBIQUE', 'MOZAMBIQUE REPUBLIC', 'REPUBLIC OF MOZAMBIQUE', 'MOZ'], true);
    }

    private function resolveApplicableTreatyRate(string $country, string $incomeType): ?WithholdingTaxTreatyRate
    {
        $country = trim($country);
        if ($country === '') {
            return null;
        }

        $paymentDate = $this->filled('payment_date')
            ? Carbon::parse((string) $this->input('payment_date'))
            : now();
        $companyId = creatorId();

        return WithholdingTaxTreatyRate::query()
            ->where('is_active', true)
            ->whereIn('income_type', [$incomeType, 'all'])
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('created_by')->orWhere('created_by', $companyId);
            })
            ->forCountry($country)
            ->activeAt($paymentDate)
            ->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$companyId])
            ->orderByRaw('CASE WHEN income_type = ? THEN 0 ELSE 1 END', [$incomeType])
            ->orderByDesc('valid_from')
            ->first();
    }

    private function vendorHasMatchingComplianceDocument(?VendorProfile $vendorProfile, string $reference): bool
    {
        if (!$vendorProfile) {
            return false;
        }

        $reference = strtolower(trim($reference));
        if ($reference === '') {
            return false;
        }

        $documents = $vendorProfile->compliance_documents;
        if (!is_array($documents) || $documents === []) {
            return false;
        }

        foreach ($documents as $document) {
            $normalizedDocument = strtolower(trim((string) $document));
            if ($normalizedDocument !== '' && str_contains($normalizedDocument, $reference)) {
                return true;
            }
        }

        return false;
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

    private function validateInternationalBankTransferChannel(Validator $validator, int $bankAccountId): void
    {
        if ($bankAccountId <= 0) {
            return;
        }

        $bankAccount = BankAccount::query()
            ->where('id', $bankAccountId)
            ->where('created_by', creatorId())
            ->first();

        if (!$bankAccount) {
            return;
        }

        $hasSettlementIdentifier = trim((string) ($bankAccount->swift_code ?? '')) !== ''
            || trim((string) ($bankAccount->iban ?? '')) !== ''
            || trim((string) ($bankAccount->routing_number ?? '')) !== '';

        if (!$hasSettlementIdentifier) {
            $validator->errors()->add(
                'bank_account_id',
                __('International bank transfers require a bank account with SWIFT, IBAN, or routing number configured.')
            );
        }
    }
}
