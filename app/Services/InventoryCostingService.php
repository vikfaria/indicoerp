<?php

namespace App\Services;

use App\Models\StockCostLayer;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Workdo\Account\Models\ChartOfAccount;
use Workdo\Account\Models\JournalEntry;
use Workdo\Account\Models\JournalEntryItem;

/**
 * FIFO inventory costing service.
 * Manages stock movements, cost layers, and automatic journal entries.
 * PGC-MZ accounts: 31 (Mercadorias), 61 (CMVMC), 32 (Matérias-primas).
 */
class InventoryCostingService
{
    /**
     * Record a purchase (inbound) movement with FIFO layer creation.
     */
    public function recordPurchase(
        int $companyId,
        int $productId,
        float $quantity,
        float $unitCost,
        string $date,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $warehouseCode = null
    ): StockMovement {
        return DB::transaction(function () use ($companyId, $productId, $quantity, $unitCost, $date, $referenceType, $referenceId, $warehouseCode) {
            $totalCost = round($quantity * $unitCost, 2);

            // Get current running totals
            $current = $this->getCurrentPosition($companyId, $productId);

            $movement = StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'movement_type' => 'purchase',
                'movement_date' => $date,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'running_quantity' => $current['quantity'] + $quantity,
                'running_value' => $current['value'] + $totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'warehouse_code' => $warehouseCode,
                'created_by' => $companyId,
            ]);

            // Create FIFO cost layer
            StockCostLayer::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'stock_movement_id' => $movement->id,
                'original_quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'unit_cost' => $unitCost,
                'entry_date' => $date,
                'is_exhausted' => false,
                'created_by' => $companyId,
            ]);

            // Journal entry: D 31 Inventário, C 221 Fornecedor
            $this->createPurchaseJournalEntry($companyId, $totalCost, $date);

            return $movement;
        });
    }

    /**
     * Record a sale (outbound) movement using FIFO costing.
     */
    public function recordSale(
        int $companyId,
        int $productId,
        float $quantity,
        string $date,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        return DB::transaction(function () use ($companyId, $productId, $quantity, $date, $referenceType, $referenceId) {
            // FIFO cost consumption
            $costResult = $this->consumeFifoLayers($companyId, $productId, $quantity);

            $current = $this->getCurrentPosition($companyId, $productId);

            $movement = StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'movement_type' => 'sale',
                'movement_date' => $date,
                'quantity' => -$quantity,
                'unit_cost' => $costResult['weighted_cost'],
                'total_cost' => -$costResult['total_cost'],
                'running_quantity' => $current['quantity'] - $quantity,
                'running_value' => $current['value'] - $costResult['total_cost'],
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $companyId,
            ]);

            // Journal entry: D 61 CMVMC, C 31 Inventário
            $this->createCostOfSalesJournalEntry($companyId, $costResult['total_cost'], $date);

            return $movement;
        });
    }

    /**
     * Get current stock position for a product.
     */
    public function getCurrentPosition(int $companyId, int $productId): array
    {
        $last = StockMovement::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->first();

        return [
            'quantity' => (float) ($last->running_quantity ?? 0),
            'value' => (float) ($last->running_value ?? 0),
            'average_cost' => $last && $last->running_quantity > 0
                ? round($last->running_value / $last->running_quantity, 4)
                : 0,
        ];
    }

    /**
     * Get stock valuation for all products (for balance sheet / inventory count).
     */
    public function getStockValuation(int $companyId, ?string $asOfDate = null): array
    {
        $query = StockCostLayer::where('company_id', $companyId)
            ->where('is_exhausted', false)
            ->where('remaining_quantity', '>', 0);

        if ($asOfDate) {
            $query->where('entry_date', '<=', $asOfDate);
        }

        $layers = $query->selectRaw('product_id, SUM(remaining_quantity) as total_qty, SUM(remaining_quantity * unit_cost) as total_value')
            ->groupBy('product_id')
            ->get();

        $totalValue = $layers->sum('total_value');

        return [
            'products' => $layers->toArray(),
            'total_value' => round($totalValue, 2),
            'product_count' => $layers->count(),
        ];
    }

    /**
     * Consume FIFO layers for an outbound movement.
     */
    private function consumeFifoLayers(int $companyId, int $productId, float $quantity): array
    {
        $layers = StockCostLayer::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->available()
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $remaining = $quantity;
        $totalCost = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $consume = min($remaining, $layer->remaining_quantity);
            $cost = round($consume * $layer->unit_cost, 2);

            $layer->remaining_quantity -= $consume;
            $layer->is_exhausted = $layer->remaining_quantity <= 0;
            $layer->save();

            $totalCost += $cost;
            $remaining -= $consume;
        }

        if ($remaining > 0) {
            // Stock insuficiente — registar com custo zero (will need adjustment)
            \Illuminate\Support\Facades\Log::warning("Stock insuficiente: product {$productId}, faltam {$remaining} unidades");
        }

        $weightedCost = $quantity > 0 ? round($totalCost / $quantity, 4) : 0;

        return [
            'total_cost' => round($totalCost, 2),
            'weighted_cost' => $weightedCost,
            'shortage' => max($remaining, 0),
        ];
    }

    private function createPurchaseJournalEntry(int $companyId, float $amount, string $date): void
    {
        $inventoryAccount = ChartOfAccount::where('account_code', '31')->where('created_by', $companyId)->first();
        $supplierAccount = ChartOfAccount::where('account_code', '221')->where('created_by', $companyId)->first();
        if (!$inventoryAccount || !$supplierAccount) return;

        $entry = JournalEntry::create([
            'journal_date' => $date, 'entry_type' => 'automatic', 'reference_type' => 'stock_purchase',
            'description' => 'Entrada de inventário', 'total_debit' => $amount, 'total_credit' => $amount,
            'status' => 'posted', 'created_by' => $companyId,
        ]);

        JournalEntryItem::create(['journal_entry_id' => $entry->id, 'account_id' => $inventoryAccount->id, 'description' => 'Compra de mercadoria', 'debit_amount' => $amount, 'credit_amount' => 0, 'created_by' => $companyId]);
        JournalEntryItem::create(['journal_entry_id' => $entry->id, 'account_id' => $supplierAccount->id, 'description' => 'Fornecedor a pagar', 'debit_amount' => 0, 'credit_amount' => $amount, 'created_by' => $companyId]);
    }

    private function createCostOfSalesJournalEntry(int $companyId, float $amount, string $date): void
    {
        $cmvmcAccount = ChartOfAccount::where('account_code', '61')->where('created_by', $companyId)->first();
        $inventoryAccount = ChartOfAccount::where('account_code', '31')->where('created_by', $companyId)->first();
        if (!$cmvmcAccount || !$inventoryAccount) return;

        $entry = JournalEntry::create([
            'journal_date' => $date, 'entry_type' => 'automatic', 'reference_type' => 'cost_of_sales',
            'description' => 'Custo das mercadorias vendidas', 'total_debit' => $amount, 'total_credit' => $amount,
            'status' => 'posted', 'created_by' => $companyId,
        ]);

        JournalEntryItem::create(['journal_entry_id' => $entry->id, 'account_id' => $cmvmcAccount->id, 'description' => 'CMVMC', 'debit_amount' => $amount, 'credit_amount' => 0, 'created_by' => $companyId]);
        JournalEntryItem::create(['journal_entry_id' => $entry->id, 'account_id' => $inventoryAccount->id, 'description' => 'Saída de inventário', 'debit_amount' => 0, 'credit_amount' => $amount, 'created_by' => $companyId]);
    }
}
