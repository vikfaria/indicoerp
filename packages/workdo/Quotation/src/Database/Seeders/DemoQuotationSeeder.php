<?php

namespace Workdo\Quotation\Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Warehouse;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\Quotation\Models\SalesQuotation;
use Workdo\Quotation\Models\SalesQuotationItem;
use Workdo\Quotation\Models\SalesQuotationItemTax;

class DemoQuotationSeeder extends Seeder
{
    public function run($userId)
    {
        if (SalesQuotation::where('created_by', $userId)->exists()) {
            return;  // All three tables contain user data → skip seeding
        }
        if (!empty($userId)) {
              // Get sample customers, warehouses and products
            $customers  = User::where('type', 'client')->where('created_by', $userId)->pluck('id')->toArray();
            $warehouses = Warehouse::where('created_by', $userId)->pluck('id')->toArray();
            $products   = ProductServiceItem::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($customers) || empty($warehouses) || empty($products)) {
                return;
            }

              // Comprehensive quotation data spanning multiple months
            $quotationRecords = [
                  // Q1 - Business Solutions
                ['date' => Carbon::now()->subDays(180), 'items' => [2, 3], 'qty' => [1, 2], 'discount' => [0, 5], 'status' => 'accepted'],
                ['date' => Carbon::now()->subDays(175), 'items' => [1, 2], 'qty' => [1, 3], 'discount' => [5, 10], 'status' => 'sent'],
                ['date' => Carbon::now()->subDays(170), 'items' => [2, 4], 'qty' => [1, 1], 'discount' => [0, 8], 'status' => 'rejected'],

                  // Q1 - Technology Services
                ['date' => Carbon::now()->subDays(165), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [0, 12], 'status' => 'draft'],
                ['date' => Carbon::now()->subDays(160), 'items' => [2, 3], 'qty' => [1, 1], 'discount' => [5, 15], 'status' => 'accepted'],
                ['date' => Carbon::now()->subDays(155), 'items' => [1, 2], 'qty' => [1, 3], 'discount' => [10, 20], 'status' => 'sent'],

                  // Q2 - Manufacturing Equipment
                ['date' => Carbon::now()->subDays(150), 'items' => [3, 5], 'qty' => [1, 2], 'discount' => [0, 10], 'status' => 'accepted'],
                ['date' => Carbon::now()->subDays(145), 'items' => [2, 4], 'qty' => [1, 2], 'discount' => [5, 18], 'status' => 'rejected'],
                ['date' => Carbon::now()->subDays(140), 'items' => [1, 3], 'qty' => [1, 1], 'discount' => [0, 12], 'status' => 'draft'],

                  // Q2 - Consulting Services
                ['date' => Carbon::now()->subDays(135), 'items' => [2, 3], 'qty' => [1, 2], 'discount' => [5, 15], 'status' => 'sent'],
                ['date' => Carbon::now()->subDays(130), 'items' => [1, 4], 'qty' => [1, 3], 'discount' => [0, 20], 'status' => 'accepted'],
                ['date' => Carbon::now()->subDays(125), 'items' => [2, 3], 'qty' => [1, 1], 'discount' => [10, 25], 'status' => 'rejected'],

                  // Q3 - Professional Services
                ['date' => Carbon::now()->subDays(120), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 8], 'status' => 'draft'],
                ['date' => Carbon::now()->subDays(115), 'items' => [2, 4], 'qty' => [1, 2], 'discount' => [5, 12], 'status' => 'sent'],
                ['date' => Carbon::now()->subDays(110), 'items' => [1, 3], 'qty' => [1, 3], 'discount' => [0, 15], 'status' => 'accepted'],

                  // Q3 - Support Services
                ['date' => Carbon::now()->subDays(105), 'items' => [2, 3], 'qty' => [1, 1], 'discount' => [5, 18], 'status' => 'sent'],
                ['date' => Carbon::now()->subDays(100), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 10], 'status' => 'rejected'],
                ['date' => Carbon::now()->subDays(95), 'items' => [3, 5], 'qty' => [1, 1], 'discount' => [10, 22], 'status' => 'draft'],

                  // Q4 - Enterprise Solutions
                ['date' => Carbon::now()->subDays(90), 'items' => [2, 4], 'qty' => [1, 3], 'discount' => [0, 12], 'status' => 'accepted'],
                ['date' => Carbon::now()->subDays(85), 'items' => [1, 3], 'qty' => [1, 1], 'discount' => [5, 15], 'status' => 'sent'],
                ['date' => Carbon::now()->subDays(80), 'items' => [2, 3], 'qty' => [1, 2], 'discount' => [0, 20], 'status' => 'rejected'],

                  // Recent Quotations - Mixed Services
                ['date' => Carbon::now()->subDays(30), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [5, 10], 'status' => 'sent'],
                ['date' => Carbon::now()->subDays(20), 'items' => [2, 4], 'qty' => [1, 1], 'discount' => [0, 15], 'status' => 'draft'],
                ['date' => Carbon::now()->subDays(2), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [10, 18], 'status' => 'draft']
            ];

            foreach ($quotationRecords as $index => $record) {
                $customerId  = $customers[array_rand($customers)];
                $warehouseId = $warehouses[array_rand($warehouses)];

                  // Get products available in this warehouse
                $warehouseProducts = ProductServiceItem::where('is_active', true)
                    ->where('created_by', $userId)
                    ->whereHas('warehouseStocks', function ($q) use ($warehouseId) {
                        $q->where('warehouse_id', $warehouseId)
                            ->where('quantity', '>', 0);
                    })
                    ->pluck('id')
                    ->toArray();

                if (empty($warehouseProducts)) {
                    continue;  // Skip this quotation if no products available in warehouse
                }

                  // Generate quotation number
                $quotationNumber = 'QT-' . $record['date']->format('Y-m') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                  // Create quotation record
                $quotation = SalesQuotation::create([
                    'quotation_number' => $quotationNumber,
                    'customer_id'      => $customerId,
                    'warehouse_id'     => $warehouseId,
                    'quotation_date'   => $record['date']->toDateString(),
                    'due_date'         => Carbon::now()->addDays(rand(15, 60))->toDateString(),
                    'status'           => $record['status'],
                    'payment_terms'    => 'Net 30',
                    'notes'            => 'Professional quotation for business services',
                    'subtotal'         => 0,
                    'tax_amount'       => 0,
                    'discount_amount'  => 0,
                    'total_amount'     => 0,
                    'creator_id'       => $userId,
                    'created_by'       => $userId,
                    'created_at'       => $record['date'],
                    'updated_at'       => $record['date'],
                ]);

                  // Generate items for this quotation
                $itemsCount       = rand($record['items'][0], min($record['items'][1], count($warehouseProducts)));
                $selectedProducts = array_rand(array_flip($warehouseProducts), min($itemsCount, count($warehouseProducts)));
                if (!is_array($selectedProducts)) {
                    $selectedProducts = [$selectedProducts];
                }

                $subtotal      = 0;
                $totalTax      = 0;
                $totalDiscount = 0;

                foreach ($selectedProducts as $productId) {
                    $product = ProductServiceItem::with('warehouseStocks')->find($productId);
                    if (!$product) continue;

                      // Get warehouse stock for validation
                    $warehouseStock = $product->warehouseStocks
                        ->where('warehouse_id', $warehouseId)
                        ->where('quantity', '>', 0)
                        ->first();

                    if (!$warehouseStock) continue;

                    $maxQuantity        = min($warehouseStock->quantity, $record['qty'][1]);
                    $quantity           = rand($record['qty'][0], max($record['qty'][0], $maxQuantity));
                    $unitPrice          = $product->sale_price ?? rand(500, 5000);
                    $discountPercentage = rand($record['discount'][0], $record['discount'][1]);
                    $taxPercentage      = rand(8, 18);

                    $lineTotal      = $quantity * $unitPrice;
                    $discountAmount = ($lineTotal * $discountPercentage) / 100;
                    $afterDiscount  = $lineTotal - $discountAmount;
                    $taxAmount      = ($afterDiscount * $taxPercentage) / 100;
                    $itemTotal      = $afterDiscount + $taxAmount;

                      // Create quotation item
                    $item = SalesQuotationItem::create([
                        'quotation_id'        => $quotation->id,
                        'product_id'          => $productId,
                        'quantity'            => $quantity,
                        'unit_price'          => $unitPrice,
                        'discount_percentage' => $discountPercentage,
                        'discount_amount'     => $discountAmount,
                        'tax_percentage'      => $taxPercentage,
                        'tax_amount'          => $taxAmount,
                        'total_amount'        => $itemTotal,
                        'created_at'          => $record['date'],
                        'updated_at'          => $record['date'],
                    ]);

                      // Add tax details
                    SalesQuotationItemTax::create([
                        'item_id'  => $item->id,
                        'tax_name' => 'GST',
                        'tax_rate' => $taxPercentage,
                    ]);

                    $subtotal      += $lineTotal;
                    $totalTax      += $taxAmount;
                    $totalDiscount += $discountAmount;
                }

                  // Update quotation totals
                $finalTotal = $subtotal + $totalTax - $totalDiscount;
                $quotation->update([
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $totalTax,
                    'discount_amount' => $totalDiscount,
                    'total_amount'    => $finalTotal,
                ]);
            }
        }
    }
}
