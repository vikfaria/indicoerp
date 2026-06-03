<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\MzVatCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Workdo\Account\Models\Customer;

class UpdateSalesInvoiceRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoiceType = $this->route('salesInvoice')?->type ?? $this->input('type');

        return [
            'invoice_date' => 'required|date',
            'operation_date' => 'nullable|date|before_or_equal:invoice_date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'customer_id' => ['required', 'integer', $this->companyClientExistsRule()],
            'warehouse_id' => [Rule::requiredIf($invoiceType === 'product'), 'nullable', 'integer', $this->companyWarehouseExistsRule()],
            'payment_terms' => 'nullable|string|max:255',
            'late_issue_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', 'min:1', $this->companyProductExistsRule($invoiceType)],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.vat_code' => 'nullable|string|max:20|exists:mz_vat_codes,code',
            'items.*.tax_exemption_reason' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.exists' => __('Selected customer does not exist.'),
            'items.required' => __('At least one item is required.'),
            'items.*.product_id.min' => __('Please select a product for each item.'),
            'items.*.quantity.min' => __('Quantity must be at least 1.'),
            'items.*.unit_price.min' => __('Unit price must be 0 or greater.'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $items = (array) $this->input('items', []);
            $vatCodes = MzVatCode::query()
                ->whereIn('code', collect($items)->pluck('vat_code')->filter()->unique()->all())
                ->get()
                ->keyBy('code');
            $customerProfile = $this->resolveCustomerFiscalProfile();
            $invoiceType = strtolower((string) ($this->route('salesInvoice')?->type ?? $this->input('type', 'service')));

            foreach ($items as $index => $item) {
                $vatCode = (string) ($item['vat_code'] ?? '');
                if ($vatCode === '' || !$vatCodes->has($vatCode)) {
                    continue;
                }

                $vatDefinition = $vatCodes[$vatCode];
                $vatType = strtolower((string) $vatDefinition->type);
                $requiresReason = in_array($vatType, ['exempt', 'not_subject'], true);
                $inputTaxPercentage = round((float) ($item['tax_percentage'] ?? 0), 4);
                $configuredVatRate = round((float) ($vatDefinition->rate ?? 0), 4);

                if ($requiresReason && trim((string) ($item['tax_exemption_reason'] ?? '')) === '') {
                    $validator->errors()->add(
                        "items.{$index}.tax_exemption_reason",
                        __('Tax exemption reason is required for exempt or not-subject VAT codes.')
                    );
                }

                if (in_array($vatType, ['exempt', 'not_subject', 'zero'], true) && $inputTaxPercentage > 0.0001) {
                    $validator->errors()->add(
                        "items.{$index}.tax_percentage",
                        __('The selected VAT code requires a 0% tax rate on the invoice line.')
                    );
                }

                if (in_array($vatType, ['normal', 'import', 'digital', 'reverse_charge'], true) && abs($inputTaxPercentage - $configuredVatRate) > 0.0001) {
                    $validator->errors()->add(
                        "items.{$index}.tax_percentage",
                        __('Tax percentage must match the configured rate for the selected VAT code.')
                    );
                }

                if ($vatType === 'reverse_charge') {
                    if ($invoiceType !== 'service') {
                        $validator->errors()->add(
                            "items.{$index}.vat_code",
                            __('Reverse-charge VAT code can only be used on service invoices.')
                        );
                    }

                    if (!$this->canApplyReverseChargeToCustomer($customerProfile)) {
                        $validator->errors()->add(
                            "items.{$index}.vat_code",
                            __('Reverse-charge VAT code requires a non-resident or export/international customer fiscal context.')
                        );
                    }
                }
            }
        });
    }

    private function resolveCustomerFiscalProfile(): ?Customer
    {
        return Customer::query()
            ->where('created_by', creatorId())
            ->where('user_id', (int) $this->input('customer_id'))
            ->first();
    }

    private function canApplyReverseChargeToCustomer(?Customer $customerProfile): bool
    {
        if (!$customerProfile) {
            return false;
        }

        if (strtolower((string) $customerProfile->fiscal_residency_status) === 'non_resident') {
            return true;
        }

        return $this->isExportOrInternationalOperation((string) $customerProfile->operation_type);
    }

    private function isExportOrInternationalOperation(string $operationType): bool
    {
        $normalized = strtolower(trim($operationType));

        return in_array($normalized, ['export', 'international', 'international_services', 'services_export', 'digital_services'], true);
    }
}
