<?php

namespace Workdo\Pos\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;

class StorePosRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', $this->companyClientExistsRule()],
            'warehouse_id' => ['required', $this->companyWarehouseExistsRule()],
            'pos_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.id' => ['required', $this->companyProductExistsRule('product')],
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
        ];
    }
}
