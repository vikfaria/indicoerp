<?php

namespace Workdo\Account\Http\Requests;

use App\Models\SalesInvoice;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Workdo\Account\Models\Customer;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'company_name' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_email' => 'required|email|max:255',
            'contact_person_mobile' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'fiscal_residency_status' => 'nullable|in:resident,non_resident',
            'customer_type' => 'nullable|in:consumer_final,public_entity,private_company,exempt,special_regime',
            'fiscal_country' => 'nullable|string|max:120',
            'vat_regime' => 'nullable|string|max:50',
            'operation_type' => 'nullable|string|max:50',
            'billing_currency_code' => 'nullable|string|size:3',
            'accounting_account_code' => 'nullable|string|max:50',
            'fiscal_identity_lock_reason' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'billing_address.name' => 'required|string|max:255',
            'billing_address.address_line_1' => 'required|string|max:255',
            'billing_address.address_line_2' => 'nullable|string|max:255',
            'billing_address.city' => 'required|string|max:255',
            'billing_address.state' => 'required|string|max:255',
            'billing_address.country' => 'required|string|max:255',
            'billing_address.zip_code' => 'required|string|max:20',
            'shipping_address.name' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.address_line_1' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.address_line_2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.state' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.country' => 'required_if:same_as_billing,false|string|max:255',
            'shipping_address.zip_code' => 'required_if:same_as_billing,false|string|max:20',
            'same_as_billing' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $taxNumber = (string) ($this->input('tax_number') ?? '');
            $requiresNuit = $this->requiresMozambicanNuit();
            $isNonResident = $this->isNonResidentFiscalParty();
            $customerType = trim((string) $this->input('customer_type', ''));
            $operationType = trim((string) $this->input('operation_type', ''));
            $billingCurrencyCode = strtoupper(trim((string) $this->input('billing_currency_code', '')));
            $fiscalCountry = trim((string) $this->input('fiscal_country', ''));

            if ($requiresNuit && !MozambiqueTaxNumber::isValidNuit($taxNumber)) {
                $validator->errors()->add('tax_number', __('NUIT must contain exactly 9 digits.'));
                return;
            }

            if ($taxNumber !== '' && !MozambiqueTaxNumber::isValidNuit($taxNumber) && $this->isMozambiqueContext() && !$isNonResident) {
                $validator->errors()->add('tax_number', __('NUIT must contain exactly 9 digits.'));
            }

            if ($isNonResident && trim((string) $this->input('fiscal_country')) === '') {
                $validator->errors()->add('fiscal_country', __('Fiscal country is required for non-resident customers.'));
            }

            if ($customerType === '') {
                $validator->errors()->add('customer_type', __('Customer fiscal classification is required.'));
            }

            if ($operationType === '') {
                $validator->errors()->add('operation_type', __('Customer operation type is required.'));
            }

            if (in_array($customerType, ['exempt', 'special_regime'], true) && trim((string) $this->input('vat_regime', '')) === '') {
                $validator->errors()->add('vat_regime', __('VAT regime is required for exempt or special-regime customers.'));
            }

            if ($isNonResident && $billingCurrencyCode === '') {
                $validator->errors()->add('billing_currency_code', __('Billing currency is required for non-resident customers.'));
            }

            $countryForCurrencyPolicy = $fiscalCountry !== ''
                ? $fiscalCountry
                : (string) data_get($this->input('billing_address', []), 'country', '');

            if (
                !$isNonResident
                && MozambiqueTaxNumber::isMozambiqueCountry($countryForCurrencyPolicy)
                && $billingCurrencyCode !== ''
                && $billingCurrencyCode !== 'MZN'
            ) {
                $validator->errors()->add(
                    'billing_currency_code',
                    __('Resident customers in domestic Mozambican operations must use MZN as billing currency.')
                );
            }

            $customer = $this->route('customer');

            if ($customer instanceof Customer && $this->hasCriticalFiscalChange($customer) && $this->customerHasFiscalHistory($customer)) {
                $reason = trim((string) $this->input('fiscal_identity_lock_reason', ''));
                $canOverride = (bool) optional($this->user())->can('manage-account-reports');

                if (!$canOverride) {
                    $validator->errors()->add(
                        'tax_number',
                        __('Critical fiscal customer data cannot be edited directly after fiscal documents are issued. Create a new customer profile or use fiscal rectification workflow.')
                    );
                } elseif ($reason === '') {
                    $validator->errors()->add(
                        'fiscal_identity_lock_reason',
                        __('Provide a fiscal lock reason to override critical customer fiscal data after document issuance.')
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

    private function hasCriticalFiscalChange(Customer $customer): bool
    {
        $incomingTaxNumber = MozambiqueTaxNumber::normalize($this->input('tax_number')) ?: '';
        $currentTaxNumber = MozambiqueTaxNumber::normalize($customer->tax_number) ?: '';

        $incomingCompanyName = trim((string) $this->input('company_name', $customer->company_name));
        $incomingResidency = strtolower((string) $this->input('fiscal_residency_status', $customer->fiscal_residency_status ?: 'resident'));
        $incomingType = strtolower((string) $this->input('customer_type', $customer->customer_type ?: ''));
        $incomingCountry = strtolower((string) $this->input('fiscal_country', $customer->fiscal_country ?: ''));

        return $incomingTaxNumber !== $currentTaxNumber
            || $incomingCompanyName !== trim((string) $customer->company_name)
            || $incomingResidency !== strtolower((string) ($customer->fiscal_residency_status ?: 'resident'))
            || $incomingType !== strtolower((string) ($customer->customer_type ?: ''))
            || $incomingCountry !== strtolower((string) ($customer->fiscal_country ?: ''));
    }

    private function customerHasFiscalHistory(Customer $customer): bool
    {
        if (!Schema::hasTable('sales_invoices') || $customer->user_id === null) {
            return false;
        }

        $query = SalesInvoice::query()
            ->where('created_by', (int) $customer->created_by)
            ->where('customer_id', (int) $customer->user_id);

        if (Schema::hasColumn('sales_invoices', 'status')) {
            $query->whereNotIn('status', ['draft']);
        }

        return $query->exists();
    }
}
