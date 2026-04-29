<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BuildsTenantScopedRules;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturnItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseReturnRequest extends FormRequest
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
            'vendor_id' => ['required', 'integer', $this->companyVendorExistsRule()],
            'warehouse_id' => ['nullable', 'integer', $this->companyWarehouseExistsRule()],
            'original_invoice_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('purchase_invoices', 'id')->where(function ($query) {
                    $query->where('created_by', creatorId())
                        ->where('vendor_id', $this->input('vendor_id'))
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
                    if (!$this->resolveOriginalPurchaseInvoiceItem($attribute, $value)) {
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
                    $originalItem = $this->resolveOriginalPurchaseInvoiceItem($attribute, $originalItemId);

                    if (!$originalItem) {
                        return;
                    }

                    $alreadyReturned = PurchaseReturnItem::query()
                        ->where('original_invoice_item_id', $originalItem->id)
                        ->whereHas('purchaseReturn', function ($query) {
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

    public function messages(): array
    {
        return [
            'vendor_id.exists' => __('Selected vendor does not exist.'),
            'items.required' => __('At least one item is required.'),
            'items.*.product_id.min' => __('Please select a product for each item.'),
            'items.*.return_quantity.min' => __('Return quantity must be at least 1.'),
            'items.*.unit_price.min' => __('Unit price must be 0 or greater.')
        ];
    }

    private function resolveOriginalPurchaseInvoiceItem(string $attribute, mixed $value): ?PurchaseInvoiceItem
    {
        $invoiceId = (int) $this->input('original_invoice_id');
        $productId = (int) $this->itemValue($attribute, 'product_id');
        $originalItemId = (int) $value;

        if (!$invoiceId || !$productId || !$originalItemId) {
            return null;
        }

        return PurchaseInvoiceItem::query()
            ->whereKey($originalItemId)
            ->where('invoice_id', $invoiceId)
            ->where('product_id', $productId)
            ->whereHas('invoice', function ($query) {
                $query->where('created_by', creatorId())
                    ->where('vendor_id', $this->input('vendor_id'))
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
