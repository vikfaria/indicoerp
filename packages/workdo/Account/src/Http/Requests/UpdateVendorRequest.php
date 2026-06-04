<?php

namespace Workdo\Account\Http\Requests;

use App\Models\PurchaseInvoice;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Models\Vendor;

class UpdateVendorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_name' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_mobile' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'fiscal_residency_status' => 'nullable|in:resident,non_resident',
            'vendor_type' => 'nullable|in:public_entity,private_company,service_provider,import_supplier,exempt,special_regime',
            'fiscal_country' => 'nullable|string|max:120',
            'vat_regime' => 'nullable|string|max:50',
            'supply_type' => 'nullable|string|max:50',
            'payment_currency_code' => 'nullable|string|size:3',
            'foreign_tax_number' => 'nullable|string|max:255',
            'withholding_tax_applicable' => 'nullable|boolean',
            'reverse_charge_applicable' => 'nullable|boolean',
            'adt_eligible' => 'nullable|boolean',
            'adt_country' => 'nullable|string|max:120',
            'compliance_documents' => 'nullable|array',
            'compliance_documents.*' => 'nullable|string|max:255',
            'fiscal_identity_lock_reason' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'billing_address' => 'required|array',
            'billing_address.name' => 'required|string|max:255',
            'billing_address.address_line_1' => 'required|string|max:255',
            'billing_address.address_line_2' => 'nullable|string|max:255',
            'billing_address.city' => 'required|string|max:255',
            'billing_address.state' => 'required|string|max:255',
            'billing_address.country' => 'required|string|max:255',
            'billing_address.zip_code' => 'required|string|max:20',
            'same_as_billing' => 'boolean',
            'shipping_address' => 'required_if:same_as_billing,false|array',
            'shipping_address.name' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.address_line_1' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.address_line_2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.state' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.country' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.zip_code' => 'required_if:same_as_billing,false|string|max:20',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $taxNumber = (string) ($this->input('tax_number') ?? '');
            $requiresNuit = $this->requiresMozambicanNuit();
            $isNonResident = $this->isNonResidentFiscalParty();
            $vendorType = trim((string) $this->input('vendor_type', ''));
            $supplyType = trim((string) $this->input('supply_type', ''));
            $paymentCurrencyCode = strtoupper(trim((string) $this->input('payment_currency_code', '')));
            $fiscalCountry = trim((string) $this->input('fiscal_country', ''));
            $isAdtEligible = $this->boolean('adt_eligible');
            $hasComplianceDocuments = collect($this->input('compliance_documents', []))
                ->filter(fn ($value) => trim((string) $value) !== '')
                ->isNotEmpty();

            if ($requiresNuit && !MozambiqueTaxNumber::isValidNuit($taxNumber)) {
                $validator->errors()->add('tax_number', __('NUIT must contain exactly 9 digits.'));
                return;
            }

            if ($taxNumber !== '' && !MozambiqueTaxNumber::isValidNuit($taxNumber) && $this->isMozambiqueContext() && !$isNonResident) {
                $validator->errors()->add('tax_number', __('NUIT must contain exactly 9 digits.'));
            }

            if ($isNonResident && trim((string) $this->input('fiscal_country')) === '') {
                $validator->errors()->add('fiscal_country', __('Fiscal country is required for non-resident vendors.'));
            }

            if ($isNonResident && trim((string) $this->input('foreign_tax_number')) === '' && trim($taxNumber) === '') {
                $validator->errors()->add('foreign_tax_number', __('Provide a foreign tax number or tax identifier for non-resident vendors.'));
            }

            if ($vendorType === '') {
                $validator->errors()->add('vendor_type', __('Vendor fiscal classification is required.'));
            }

            if ($supplyType === '') {
                $validator->errors()->add('supply_type', __('Supply type is required for vendor fiscal classification.'));
            }

            if (in_array($vendorType, ['exempt', 'special_regime'], true) && trim((string) $this->input('vat_regime', '')) === '') {
                $validator->errors()->add('vat_regime', __('VAT regime is required for exempt or special-regime vendors.'));
            }

            if ($isNonResident && $paymentCurrencyCode === '') {
                $validator->errors()->add('payment_currency_code', __('Payment currency is required for non-resident vendors.'));
            }

            $countryForCurrencyPolicy = $fiscalCountry !== ''
                ? $fiscalCountry
                : (string) data_get($this->input('billing_address', []), 'country', '');

            if (
                !$isNonResident
                && MozambiqueTaxNumber::isMozambiqueCountry($countryForCurrencyPolicy)
                && $paymentCurrencyCode !== ''
                && $paymentCurrencyCode !== 'MZN'
            ) {
                $validator->errors()->add(
                    'payment_currency_code',
                    __('Resident vendors in domestic Mozambican operations must use MZN as payment currency.')
                );
            }

            if ($this->boolean('reverse_charge_applicable') && !$isNonResident) {
                $validator->errors()->add('reverse_charge_applicable', __('Reverse charge can only be enabled for non-resident vendors.'));
            }

            if ($isAdtEligible) {
                if (!$isNonResident) {
                    $validator->errors()->add('adt_eligible', __('ADT eligibility can only be enabled for non-resident vendors.'));
                }

                if (trim((string) $this->input('adt_country', '')) === '') {
                    $validator->errors()->add('adt_country', __('ADT country is required when vendor is marked as ADT-eligible.'));
                }

                if (trim((string) $this->input('foreign_tax_number', '')) === '') {
                    $validator->errors()->add('foreign_tax_number', __('Foreign tax number is required when vendor is marked as ADT-eligible.'));
                }

                if (!$hasComplianceDocuments) {
                    $validator->errors()->add('compliance_documents', __('At least one compliance document is required when vendor is marked as ADT-eligible.'));
                }
            }

            $vendor = $this->route('vendor');
            $changedCriticalFields = $vendor instanceof Vendor
                ? $this->changedCriticalFiscalFields($vendor)
                : [];

            if ($vendor instanceof Vendor && $changedCriticalFields !== [] && $this->vendorHasFiscalHistory($vendor)) {
                $reason = trim((string) $this->input('fiscal_identity_lock_reason', ''));
                $canOverride = (bool) optional($this->user())->can('manage-account-reports');

                if (!$canOverride) {
                    $validator->errors()->add(
                        'tax_number',
                        __('Critical fiscal vendor data cannot be edited directly after fiscal documents are issued. Create a new vendor profile or use fiscal rectification workflow.')
                    );

                    foreach ($changedCriticalFields as $field) {
                        $validator->errors()->add(
                            $field,
                            __('Critical fiscal vendor data cannot be edited directly after fiscal documents are issued. Create a new vendor profile or use fiscal rectification workflow.')
                        );
                    }
                } elseif ($reason === '') {
                    $validator->errors()->add(
                        'fiscal_identity_lock_reason',
                        __('Provide a fiscal lock reason to override critical vendor fiscal data after document issuance.')
                    );
                }
            }
        });
    }

    private function requiresMozambicanNuit(): bool
    {
        if ($this->isNonResidentFiscalParty()) {
            return false;
        }

        $taxType = strtoupper((string) company_setting('tax_type', creatorId()));

        return $taxType === 'NUIT' || $this->isMozambiqueContext();
    }

    private function isMozambiqueContext(): bool
    {
        $billingCountry = (string) data_get($this->input('billing_address', []), 'country', '');
        $shippingCountry = (string) data_get($this->input('shipping_address', []), 'country', '');
        $companyCountry = (string) company_setting('company_country', creatorId());

        return MozambiqueTaxNumber::isMozambiqueCountry($billingCountry)
            || MozambiqueTaxNumber::isMozambiqueCountry($shippingCountry)
            || MozambiqueTaxNumber::isMozambiqueCountry($companyCountry);
    }

    private function isNonResidentFiscalParty(): bool
    {
        return strtolower((string) $this->input('fiscal_residency_status', 'resident')) === 'non_resident';
    }

    private function hasCriticalFiscalChange(Vendor $vendor): bool
    {
        return $this->changedCriticalFiscalFields($vendor) !== [];
    }

    /**
     * @return array<int, string>
     */
    private function changedCriticalFiscalFields(Vendor $vendor): array
    {
        $criticalFields = $this->criticalFiscalFields();
        $changedFields = [];

        foreach ($criticalFields as $field) {
            $currentValue = $vendor->getAttribute($field);
            $incomingValue = $this->input($field, $currentValue);

            if ($this->normalizeCriticalFiscalValue($field, $incomingValue) !== $this->normalizeCriticalFiscalValue($field, $currentValue)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * @return array<int, string>
     */
    private function criticalFiscalFields(): array
    {
        return [
            'tax_number',
            'company_name',
            'fiscal_residency_status',
            'vendor_type',
            'fiscal_country',
            'vat_regime',
            'supply_type',
            'payment_currency_code',
            'foreign_tax_number',
            'withholding_tax_applicable',
            'reverse_charge_applicable',
            'adt_eligible',
            'adt_country',
        ];
    }

    private function normalizeCriticalFiscalValue(string $field, mixed $value): string
    {
        return match ($field) {
            'tax_number', 'foreign_tax_number' => MozambiqueTaxNumber::normalize(is_string($value) ? $value : null) ?: '',
            'withholding_tax_applicable', 'reverse_charge_applicable', 'adt_eligible' => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            'payment_currency_code' => strtoupper(trim((string) $value)),
            default => strtolower(trim((string) $value)),
        };
    }

    private function vendorHasFiscalHistory(Vendor $vendor): bool
    {
        if (!Schema::hasTable('purchase_invoices') || $vendor->user_id === null) {
            return false;
        }

        $query = PurchaseInvoice::query()
            ->where('created_by', (int) $vendor->created_by)
            ->where('vendor_id', (int) $vendor->user_id);

        if (Schema::hasColumn('purchase_invoices', 'status')) {
            $query->whereNotIn('status', ['draft']);
        }

        return $query->exists();
    }
}
