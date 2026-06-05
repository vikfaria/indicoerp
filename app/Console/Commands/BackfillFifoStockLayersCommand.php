<?php

namespace App\Console\Commands;

use App\Services\InventoryCostingService;
use Illuminate\Console\Command;
use Workdo\ProductService\Models\WarehouseStock;

class BackfillFifoStockLayersCommand extends Command
{
    protected $signature = 'inventory:backfill-fifo-layers
        {--company= : Company ID to backfill. If omitted, all companies with warehouse stock are processed.}
        {--product= : Optional product ID filter.}
        {--warehouse= : Optional warehouse ID filter.}
        {--dry-run : Preview the backfill without creating FIFO layers.}
        {--with-journal : Create accounting journals for the backfilled layers.}';

    protected $description = 'Backfill missing FIFO stock layers from existing warehouse stock balances.';

    public function handle(InventoryCostingService $inventoryCostingService): int
    {
        $companyId = $this->option('company') ? (int) $this->option('company') : null;
        $productId = $this->option('product') ? (int) $this->option('product') : null;
        $warehouseId = $this->option('warehouse') ? (int) $this->option('warehouse') : null;
        $dryRun = (bool) $this->option('dry-run');
        $withJournal = (bool) $this->option('with-journal');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║  FIFO Stock Layer Backfill                    ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        $stocks = WarehouseStock::query()
            ->select([
                'warehouse_stocks.*',
                'product_service_items.created_by as company_id',
                'product_service_items.name as product_name',
                'product_service_items.sku as product_sku',
                'product_service_items.purchase_price as product_purchase_price',
                'warehouses.name as warehouse_name',
            ])
            ->join('product_service_items', 'product_service_items.id', '=', 'warehouse_stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
            ->where('warehouses.is_active', true)
            ->where('product_service_items.type', '!=', 'service')
            ->when($companyId, fn ($query) => $query->where('product_service_items.created_by', $companyId))
            ->when($productId, fn ($query) => $query->where('warehouse_stocks.product_id', $productId))
            ->when($warehouseId, fn ($query) => $query->where('warehouse_stocks.warehouse_id', $warehouseId))
            ->orderBy('product_service_items.created_by')
            ->orderBy('warehouse_stocks.product_id')
            ->orderBy('warehouse_stocks.warehouse_id')
            ->get();

        if ($stocks->isEmpty()) {
            $this->warn('No warehouse stock records found for the selected filters.');
            return self::SUCCESS;
        }

        $summary = [
            'evaluated' => 0,
            'backfilled' => 0,
            'skipped' => 0,
            'overstock' => 0,
        ];

        foreach ($stocks as $stock) {
            $summary['evaluated']++;

            $resolvedCompanyId = (int) $stock->company_id;
            $warehouseCode = (string) $stock->warehouse_id;
            $targetQuantity = (float) $stock->quantity;
            $availableQuantity = $inventoryCostingService->getAvailableFifoQuantity($resolvedCompanyId, (int) $stock->product_id, $warehouseCode);
            $missingQuantity = round($targetQuantity - $availableQuantity, 4);

            if ($missingQuantity <= 0) {
                if ($availableQuantity > $targetQuantity) {
                    $summary['overstock']++;
                    $this->warn(sprintf(
                        'OVERSTOCK  company=%d product=%d warehouse=%d visible=%.4f fifo=%.4f',
                        $resolvedCompanyId,
                        $stock->product_id,
                        $stock->warehouse_id,
                        $targetQuantity,
                        $availableQuantity
                    ));
                } else {
                    $summary['skipped']++;
                    $this->line(sprintf(
                        'SKIP      company=%d product=%d warehouse=%d quantity=%.4f (already covered)',
                        $resolvedCompanyId,
                        $stock->product_id,
                        $stock->warehouse_id,
                        $targetQuantity
                    ));
                }
                continue;
            }

            $unitCost = $inventoryCostingService->resolveBackfillUnitCost($resolvedCompanyId, (int) $stock->product_id, $warehouseCode);

            if ($dryRun) {
                $summary['backfilled']++;
                $this->line(sprintf(
                    'DRY-RUN   company=%d product=%d warehouse=%d missing=%.4f unit_cost=%.4f',
                    $resolvedCompanyId,
                    $stock->product_id,
                    $stock->warehouse_id,
                    $missingQuantity,
                    $unitCost
                ));
                continue;
            }

            $movement = $inventoryCostingService->backfillMissingWarehouseStockLayers(
                $resolvedCompanyId,
                (int) $stock->product_id,
                (int) $stock->warehouse_id,
                $targetQuantity,
                'fifo_backfill',
                null,
                $withJournal
            );

            if ($movement) {
                $summary['backfilled']++;
                $this->line(sprintf(
                    'BACKFILL  company=%d product=%d warehouse=%d missing=%.4f unit_cost=%.4f movement=%d',
                    $resolvedCompanyId,
                    $stock->product_id,
                    $stock->warehouse_id,
                    $missingQuantity,
                    $unitCost,
                    $movement->id
                ));
            } else {
                $summary['skipped']++;
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. evaluated=%d backfilled=%d skipped=%d overstock=%d%s',
            $summary['evaluated'],
            $summary['backfilled'],
            $summary['skipped'],
            $summary['overstock'],
            $dryRun ? ' (dry-run)' : ''
        ));

        return self::SUCCESS;
    }
}
