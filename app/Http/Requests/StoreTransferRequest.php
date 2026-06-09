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
            'carrier_name' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:64'],
            'driver_name' => ['nullable', 'string', 'max:255'],
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
                        $fail(__('A quantidade não pode exceder o stock FIFO disponível (:quantity).', [
                            'quantity' => $availableQty,
                        ]));
                    }
                }
            ],
            'date' => 'required|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $fields = ['carrier_name', 'vehicle_plate', 'driver_name'];
            $filledFields = array_filter($fields, fn (string $field): bool => filled($this->input($field)));

            if (count($filledFields) > 0 && count($filledFields) < count($fields)) {
                $message = __('Todos os dados de transporte são obrigatórios para emitir uma Guia de Transporte completa.');

                foreach ($fields as $field) {
                    if (! filled($this->input($field))) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }
}
