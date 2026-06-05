<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\StockCostLayer;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse' => ['required', $this->companyWarehouseExistsRule()],
            'to_warehouse' => ['required', 'different:from_warehouse', $this->companyWarehouseExistsRule()],
            'product_id' => ['required', $this->companyProductExistsRule('product')],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $availableQty = StockCostLayer::query()
                        ->where('company_id', creatorId())
                        ->where('product_id', $this->product_id)
                        ->available()
                        ->whereHas('movement', function ($query) {
                            $query->where('warehouse_code', (string) $this->from_warehouse);
                        })
                        ->sum('remaining_quantity');

                    if ($availableQty <= 0 || $value > $availableQty) {
                        $fail("Quantity cannot exceed available FIFO stock ({$availableQty}).");
                    }
                }
            ],
            'date' => 'required|date',
        ];
    }
}
