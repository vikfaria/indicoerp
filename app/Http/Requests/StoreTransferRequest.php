<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use Illuminate\Foundation\Http\FormRequest;
use Workdo\ProductService\Models\WarehouseStock;

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
                    $warehouseStock = WarehouseStock::where('warehouse_id', $this->from_warehouse)
                        ->where('product_id', $this->product_id)
                        ->whereHas('warehouse', function ($query) {
                            $query->where('created_by', creatorId())
                                ->where('is_active', true);
                        })
                        ->whereHas('product', function ($query) {
                            $query->where('created_by', creatorId())
                                ->where('is_active', true)
                                ->where('type', 'product');
                        })
                        ->first();

                    if (!$warehouseStock || $value > $warehouseStock->quantity) {
                        $availableQty = $warehouseStock ? $warehouseStock->quantity : 0;
                        $fail("Quantity cannot exceed available stock ({$availableQty}).");
                    }
                }
            ],
            'date' => 'required|date',
        ];
    }
}
