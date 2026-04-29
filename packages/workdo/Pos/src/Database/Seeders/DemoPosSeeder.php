<?php

namespace Workdo\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Pos\Models\Pos;
use Workdo\Pos\Models\PosItem;
use Workdo\Pos\Models\PosPayment;
use App\Models\User;
use App\Models\Warehouse;
use Workdo\ProductService\Models\ProductServiceItem;
use Workdo\ProductService\Models\ProductServiceTax;
use Workdo\ProductService\Models\WarehouseStock;
use Carbon\Carbon;

class DemoPosSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            // Get required data with proper validation
            $customers = User::whereHas('roles', function($q) { 
                $q->where('name', 'client'); 
            })->where('created_by', $userId)->pluck('id')->toArray();
            
            $warehouses = Warehouse::where('created_by', $userId)
                ->where('is_active', true)
                ->pluck('id')->toArray();
            
            $products = ProductServiceItem::where('created_by', $userId)
                ->where('type', '!=', 'service')
                ->where('is_active', true)
                ->with(['warehouseStocks' => function($q) use ($warehouses) {
                    $q->whereIn('warehouse_id', $warehouses)->where('quantity', '>', 0);
                }])
                ->get()
                ->filter(function($product) {
                    return $product->warehouseStocks->isNotEmpty();
                })
                ->pluck('id')
                ->toArray();
            
            $taxes = ProductServiceTax::where('created_by', $userId)->get();
            
            if (empty($warehouses) || empty($products)) {
                return;
            }

            // 30 realistic POS sales records with proper business scenarios
            $posRecords = [
                // Week 1 - Electronics & Tech Sales
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(180), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 50], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(175), 'items' => [2, 3], 'qty' => [1, 3], 'discount' => [5, 25], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(170), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [10, 40], 'status' => 'completed'],
                
                // Week 2 - Fashion & Apparel
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(165), 'items' => [2, 4], 'qty' => [1, 3], 'discount' => [0, 20], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(160), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 15], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(155), 'items' => [3, 5], 'qty' => [1, 4], 'discount' => [5, 30], 'status' => 'completed'],
                
                // Week 3 - Grocery & Food Items
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(150), 'items' => [4, 6], 'qty' => [2, 5], 'discount' => [0, 10], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(145), 'items' => [3, 4], 'qty' => [1, 3], 'discount' => [0, 12], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(140), 'items' => [2, 3], 'qty' => [2, 4], 'discount' => [0, 8], 'status' => 'completed'],
                
                // Week 4 - Home & Garden
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(135), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [0, 25], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(130), 'items' => [2, 4], 'qty' => [1, 3], 'discount' => [5, 20], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(125), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 15], 'status' => 'completed'],
                
                // Week 5 - Sports & Fitness
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(120), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 18], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(115), 'items' => [2, 3], 'qty' => [1, 3], 'discount' => [5, 22], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(110), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [0, 12], 'status' => 'completed'],
                
                // Week 6 - Health & Beauty
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(105), 'items' => [2, 4], 'qty' => [1, 3], 'discount' => [0, 28], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(100), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 15], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(95), 'items' => [3, 5], 'qty' => [1, 4], 'discount' => [10, 35], 'status' => 'completed'],
                
                // Week 7 - Books & Stationery
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(90), 'items' => [2, 5], 'qty' => [1, 3], 'discount' => [0, 10], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(85), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [0, 12], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(80), 'items' => [2, 4], 'qty' => [1, 3], 'discount' => [0, 8], 'status' => 'completed'],
                
                // Week 8 - Automotive & Tools
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(75), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [5, 35], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(70), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 20], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(65), 'items' => [2, 3], 'qty' => [1, 3], 'discount' => [10, 25], 'status' => 'completed'],
                
                // Week 9 - Jewelry & Accessories
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(60), 'items' => [1, 2], 'qty' => [1, 1], 'discount' => [0, 100], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(55), 'items' => [1, 2], 'qty' => [1, 2], 'discount' => [0, 50], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(50), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [5, 75], 'status' => 'completed'],
                
                // Recent Sales - Mixed Categories
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(7), 'items' => [2, 4], 'qty' => [1, 3], 'discount' => [0, 15], 'status' => 'completed'],
                ['customer_type' => 'registered', 'date' => Carbon::now()->subDays(3), 'items' => [1, 3], 'qty' => [1, 2], 'discount' => [5, 25], 'status' => 'completed'],
                ['customer_type' => 'walk_in', 'date' => Carbon::now()->subDays(1), 'items' => [2, 3], 'qty' => [1, 3], 'discount' => [0, 12], 'status' => 'completed']
            ];

            foreach ($posRecords as $index => $record) {
                // Generate sale number following POS controller logic
                $saleNumber = '#POS' . str_pad($index + 1, 5, '0', STR_PAD_LEFT);
                $customerId = null;
                
                // Assign customer based on type (70% registered, 30% walk-in)
                if ($record['customer_type'] === 'registered' && !empty($customers)) {
                    $customerId = $customers[array_rand($customers)];
                }
                
                $warehouseId = $warehouses[array_rand($warehouses)];
                
                // Create POS record with proper timestamps
                $pos = Pos::create([
                    'sale_number' => $saleNumber,
                    'customer_id' => $customerId,
                    'warehouse_id' => $warehouseId,
                    'pos_date' => $record['date']->toDateString(),
                    'status' => $record['status'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                    'created_at' => $record['date'],
                    'updated_at' => $record['date'],
                ]);

                // Generate realistic items for this sale
                $itemsCount = rand($record['items'][0], $record['items'][1]);
                $selectedProducts = array_rand(array_flip($products), min($itemsCount, count($products)));
                if (!is_array($selectedProducts)) {
                    $selectedProducts = [$selectedProducts];
                }
                
                $totalAmount = 0;
                
                foreach ($selectedProducts as $productId) {
                    $product = ProductServiceItem::with('warehouseStocks')->find($productId);
                    if (!$product) continue;
                    
                    // Get warehouse stock for this product
                    $warehouseStock = $product->warehouseStocks
                        ->where('warehouse_id', $warehouseId)
                        ->where('quantity', '>', 0)
                        ->first();
                    
                    if (!$warehouseStock) {
                        // Try another warehouse if current doesn't have stock
                        $warehouseStock = $product->warehouseStocks
                            ->where('quantity', '>', 0)
                            ->first();
                        if ($warehouseStock) {
                            $warehouseId = $warehouseStock->warehouse_id;
                            $pos->update(['warehouse_id' => $warehouseId]);
                        } else {
                            continue; // Skip this product if no stock available
                        }
                    }
                    
                    $maxQuantity = min($warehouseStock->quantity, $record['qty'][1]);
                    $quantity = rand($record['qty'][0], max($record['qty'][0], $maxQuantity));
                    $price = $product->sale_price;
                    $subtotal = $quantity * $price;
                    
                    // Calculate tax amount following POS controller logic
                    $taxAmount = 0;
                    $taxIds = null;
                    
                    if ($product->tax_ids && is_array($product->tax_ids) && !empty($product->tax_ids)) {
                        $taxIds = $product->tax_ids;
                        $productTaxes = $taxes->whereIn('id', $taxIds);
                        
                        foreach ($productTaxes as $tax) {
                            $taxAmount += $subtotal * ($tax->rate / 100);
                        }
                    }
                    
                    $itemTotal = $subtotal + $taxAmount;
                    $totalAmount += $itemTotal;
                    
                    // Create POS item following exact controller structure
                    PosItem::create([
                        'pos_id' => $pos->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $price,
                        'tax_ids' => $taxIds,
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $itemTotal,
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => $record['date'],
                        'updated_at' => $record['date'],
                    ]);
                    
                    // Update warehouse stock (simulate real POS behavior)
                    if ($warehouseStock->quantity >= $quantity) {
                        $warehouseStock->decrement('quantity', $quantity);
                    }
                }
                
                // Apply realistic discount following controller logic
                $discountAmount = 0;
                if ($totalAmount > 0) {
                    $discountPercentage = rand($record['discount'][0], $record['discount'][1]);
                    $discountAmount = ($totalAmount * $discountPercentage) / 100;
                    
                    // Round discount to nearest 0.50 for realistic pricing
                    $discountAmount = round($discountAmount * 2) / 2;
                }
                
                $finalAmount = $totalAmount - $discountAmount;
                
                // Create payment record following exact controller structure
                PosPayment::create([
                    'pos_id' => $pos->id,
                    'discount' => $discountAmount,
                    'amount' => $totalAmount,
                    'discount_amount' => $finalAmount,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                    'created_at' => $record['date'],
                    'updated_at' => $record['date'],
                ]);
            }
        }
    }
}