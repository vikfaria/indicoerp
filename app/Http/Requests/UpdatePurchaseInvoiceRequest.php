<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Support\MozambiqueTaxNumber;
use Illuminate\Foundation\Http\FormRequest;
use Workdo\Account\Models\Vendor;

class UpdatePurchaseInvoiceRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'vendor_id' => ['required', $this->companyVendorExistsRule()],
            'warehouse_id' => ['required', $this->companyWarehouseExistsRule()],
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', 'min:1', $this->companyProductExistsRule()],
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_id.exists' => __('Selected vendor does not exist.'),
            'items.required' => __('At least one item is required.'),
            'items.*.product_id.min' => __('Please select a product for each item.'),
            'items.*.quantity.min' => __('Quantity must be at least 1.'),
            'items.*.unit_price.min' => __('Unit price must be 0 or greater.')
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!$this->hasTaxableItems() || !$this->isMozambiqueFiscalContext()) {
                return;
            }

            $vendorId = (int) $this->input('vendor_id');
            if ($vendorId <= 0) {
                return;
            }

            $vendorNuit = Vendor::where('user_id', $vendorId)
                ->where('created_by', creatorId())
                ->value('tax_number');

            if (!MozambiqueTaxNumber::isValidNuit((string) $vendorNuit)) {
                $validator->errors()->add(
                    'vendor_id',
                    __('Fornecedor sem NUIT válido. Para operações com IVA em Moçambique, o NUIT é obrigatório.')
                );
            }
        });
    }

    private function hasTaxableItems(): bool
    {
        return collect((array) $this->input('items', []))
            ->contains(static fn ($item): bool => (float) data_get($item, 'tax_percentage', 0) > 0);
    }

    private function isMozambiqueFiscalContext(): bool
    {
        $taxType = strtoupper((string) company_setting('tax_type', creatorId()));
        if ($taxType === 'NUIT') {
            return true;
        }

        $companyCountry = (string) company_setting('company_country', creatorId());

        return MozambiqueTaxNumber::isMozambiqueCountry($companyCountry);
    }
}
