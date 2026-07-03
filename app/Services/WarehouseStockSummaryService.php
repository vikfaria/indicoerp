<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class WarehouseStockSummaryService
{
    /**
     * Build a normalized warehouse stock summary for frontend views.
     *
     * @param  Collection<int, mixed>|array<int, mixed>|null  $warehouseStocks
     * @return array<string, mixed>
     */
    public function summarize(Collection|array|null $warehouseStocks): array
    {
        $stocks = collect($warehouseStocks ?? [])
            ->map(function ($stock): array {
                $quantity = round((float) data_get($stock, 'quantity', 0), 2);
                $updatedAt = data_get($stock, 'updated_at');

                if ($updatedAt instanceof CarbonInterface) {
                    $updatedAt = $updatedAt->toDateTimeString();
                } elseif (! is_string($updatedAt) || $updatedAt === '') {
                    $updatedAt = null;
                }

                return [
                    'warehouse_id' => (int) data_get($stock, 'warehouse_id', 0),
                    'warehouse_name' => data_get($stock, 'warehouse.name')
                        ?? data_get($stock, 'warehouse_name')
                        ?? __('Warehouse'),
                    'quantity' => $quantity,
                    'status' => $quantity > 0 ? 'available' : 'empty',
                    'status_label' => $quantity > 0 ? __('Available') : __('Out of stock'),
                    'updated_at' => $updatedAt,
                    'share_percent' => 0,
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        $totalQuantity = round($stocks->sum('quantity'), 2);
        $warehouseCount = $stocks->count();
        $activeWarehouseCount = $stocks->where('quantity', '>', 0)->count();
        $status = $this->resolveStatus($totalQuantity, $activeWarehouseCount);
        $statusLabel = $this->resolveStatusLabel($status);

        $lastUpdatedAt = $stocks
            ->pluck('updated_at')
            ->filter()
            ->sortDesc()
            ->first();

        $stocks = $stocks->map(function (array $stock) use ($totalQuantity): array {
            $stock['share_percent'] = $totalQuantity > 0
                ? round(($stock['quantity'] / $totalQuantity) * 100, 1)
                : 0;

            return $stock;
        });

        return [
            'total_quantity' => $totalQuantity,
            'warehouse_count' => $warehouseCount,
            'active_warehouse_count' => $activeWarehouseCount,
            'status' => $status,
            'status_label' => $statusLabel,
            'last_updated_at' => $lastUpdatedAt,
            'warehouse_stocks' => $stocks->all(),
        ];
    }

    private function resolveStatus(float $totalQuantity, int $activeWarehouseCount): string
    {
        if ($totalQuantity <= 0) {
            return 'empty';
        }

        if ($activeWarehouseCount > 1) {
            return 'distributed';
        }

        return 'available';
    }

    private function resolveStatusLabel(string $status): string
    {
        return match ($status) {
            'empty' => __('Out of stock'),
            'distributed' => __('Distributed'),
            default => __('Available'),
        };
    }
}
