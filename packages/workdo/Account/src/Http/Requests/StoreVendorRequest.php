<?php

namespace Workdo\Account\Http\Requests;

use App\Support\MozambiqueTaxNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $userId = $this->input('user_id');

        if ($userId === '' || $userId === '0' || $userId === 0) {
            $userId = null;
        }

        $this->merge([
            'user_id' => $userId,
        ]);
    }

    public function rules()
    {
        return [
            'user_id' => 'nullable|exists:users,id',
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
}
