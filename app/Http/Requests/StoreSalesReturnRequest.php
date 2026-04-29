<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceReturnItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesReturnRequest extends FormRequest
{
    use BuildsTenantScopedRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'return_date' => 'required|date',
            'customer_id' => ['required', 'integer', 'min:1', $this->companyClientExistsRule()],
            'warehouse_id' => ['nullable', 'integer', 'min:1', $this->companyWarehouseExistsRule()],
            'original_invoice_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('sales_invoices', 'id')->where(function ($query) {
                    $query->where('created_by', creatorId())
                        ->where('customer_id', $this->input('customer_id'))
                        ->where('type', 'product')
                        ->where('status', '!=', 'draft');
                }),
            ],
            'reason' => 'required|in:defective,wrong_item,damaged,excess_quantity,other',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', 'integer', 'min:1', $this->companyProductExistsRule('product')],
            'items.*.original_invoice_item_id' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (!$this->resolveOriginalSalesInvoiceItem($attribute, $value)) {
                        $fail(__('Selected invoice item does not match the chosen invoice and product.'));
                    }
                },
            ],
            'items.*.return_quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $originalItemId = $this->itemValue($attribute, 'original_invoice_item_id');
                    $originalItem = $this->resolveOriginalSalesInvoiceItem($attribute, $originalItemId);

                    if (!$originalItem) {
                        return;
                    }

                    $alreadyReturned = SalesInvoiceReturnItem::query()
                        ->where('original_invoice_item_id', $originalItem->id)
                        ->whereHas('salesReturn', function ($query) {
                            $query->where('created_by', creatorId())
                                ->where('status', '!=', 'cancelled');
                        })
                        ->sum('return_quantity');

                    $requestedQuantity = $this->requestedQuantityForOriginalItem((int) $originalItem->id);
                    $availableQuantity = max(0, $originalItem->quantity - $alreadyReturned);

                    if ($requestedQuantity > $availableQuantity) {
                        $fail(__('Return quantity exceeds the remaining available quantity (:qty).', [
                            'qty' => $availableQuantity,
                        ]));
                    }
                },
            ],
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string'
        ];
    }

    private function resolveOriginalSalesInvoiceItem(string $attribute, mixed $value): ?SalesInvoiceItem
    {
        $invoiceId = (int) $this->input('original_invoice_id');
        $productId = (int) $this->itemValue($attribute, 'product_id');
        $originalItemId = (int) $value;

        if (!$invoiceId || !$productId || !$originalItemId) {
            return null;
        }

        return SalesInvoiceItem::query()
            ->whereKey($originalItemId)
            ->where('invoice_id', $invoiceId)
            ->where('product_id', $productId)
            ->whereHas('invoice', function ($query) {
                $query->where('created_by', creatorId())
                    ->where('customer_id', $this->input('customer_id'))
                    ->where('type', 'product')
                    ->where('status', '!=', 'draft');
            })
            ->first();
    }

    private function requestedQuantityForOriginalItem(int $originalItemId): int
    {
        return collect($this->input('items', []))
            ->filter(fn ($item) => (int) ($item['original_invoice_item_id'] ?? 0) === $originalItemId)
            ->sum(fn ($item) => (int) ($item['return_quantity'] ?? 0));
    }

    private function itemValue(string $attribute, string $field): mixed
    {
        $itemIndex = $this->resolveItemIndex($attribute);

        if ($itemIndex === null) {
            return null;
        }

        return data_get($this->input('items', []), "{$itemIndex}.{$field}");
    }

    private function resolveItemIndex(string $attribute): ?int
    {
        if (!preg_match('/^items\.(\d+)\./', $attribute, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
