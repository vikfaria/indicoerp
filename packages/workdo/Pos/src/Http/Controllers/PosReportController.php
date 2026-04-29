<?php

namespace Workdo\Pos\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Pos\Models\PosSale;
use Workdo\Pos\Models\PosItem;
use Workdo\ProductService\Models\ProductServiceItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Workdo\Pos\Models\Pos;

class PosReportController extends Controller
{
    public function sales(Request $request)
    {
        if(Auth::user()->can('manage-pos-reports')){
            $creatorId = creatorId();
            
            // Sales data for the report
            $salesData = Pos::where('created_by', $creatorId)
                ->with(['customer:id,name', 'warehouse:id,name', 'items'])
                ->latest()
                ->paginate(20);

            // Calculate total from pos_sale_items
            $salesWithTotals = $salesData->getCollection()->map(function($sale) {
                $sale->total = $sale->items->sum('total_amount');
                return $sale;
            });
            $salesData->setCollection($salesWithTotals);

            $isDemo = config('app.is_demo');

            // Daily sales for last 7 days
            $dailySales = [];
            // Volatile daily trend for POS (last 7 days)
            $dailyTrend = [1200, 1500, 1100, 1800, 1400, 2200, 1900];
            
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $sales = PosItem::where('created_by', $creatorId)
                    ->whereDate('created_at', $date)
                    ->sum('total_amount');
                $count = Pos::where('created_by', $creatorId)
                    ->whereDate('created_at', $date)
                    ->count();

                if ($isDemo && $sales < 500) {
                    $sales = $dailyTrend[6-$i] + rand(-150, 150) + rand(0, 99) / 100;
                    $count = rand(3, 8);
                }

                $dailySales[] = [
                    'date' => $date->format('M d'),
                    'sales' => $sales,
                    'count' => $count
                ];
            }

            // Monthly sales for last 6 months
            $monthlySales = [];
            // Volatile monthly growth trend
            $monthTrend = [35000, 28000, 42000, 31000, 45000, 39000];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $sales = PosItem::where('created_by', $creatorId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_amount');
                $count = Pos::where('created_by', $creatorId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                if ($isDemo && $sales < 5000) {
                    $sales = $monthTrend[5-$i] + rand(-3000, 3000) + rand(0, 99) / 100;
                    $count = rand(80, 150);
                }

                $monthlySales[] = [
                    'month' => $date->format('M Y'),
                    'sales' => $sales,
                    'count' => $count
                ];
            }

            // Warehouse sales
            $warehouseSales = PosItem::where('created_by', $creatorId)
                ->with('sale.warehouse')
                ->get()
                ->groupBy('sale.warehouse_id')
                ->map(function($items, $warehouseId) use ($isDemo) {
                    $warehouse = $items->first()->sale->warehouse;
                    $sales = $items->sum('total_amount');
                    $count = $items->pluck('pos_id')->unique()->count();

                    if ($isDemo && $sales < 1000) {
                        $sales = rand(15000, 35000) + rand(0, 99) / 100;
                        $count = rand(25, 45);
                    }

                    return [
                        'name' => $warehouse->name ?? 'Unknown',
                        'sales' => $sales,
                        'count' => $count
                    ];
                })
                ->values();

            // If warehouse sales is empty in demo mode, provide realistic defaults
            if ($isDemo && $warehouseSales->isEmpty()) {
                $warehouseSales = collect([
                    ['name' => 'Main Warehouse', 'sales' => rand(45000, 65000) + .45, 'count' => rand(55, 80)],
                    ['name' => 'North Branch', 'sales' => rand(25000, 40000) + .12, 'count' => rand(30, 50)],
                    ['name' => 'South Station', 'sales' => rand(15000, 25000) + .89, 'count' => rand(15, 30)],
                ]);
            }

            return Inertia::render('Pos/Reports/Sales', [
                'salesData' => $salesData,
                'dailySales' => $dailySales,
                'monthlySales' => $monthlySales,
                'warehouseSales' => $warehouseSales,
            ]);
        }else{
            return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function products(Request $request)
    {
        if(Auth::user()->can('manage-pos-reports')){
            $creatorId = creatorId();
            
            // Product performance data
            $productData = PosItem::where('created_by', $creatorId)
                ->with('product')
                ->get()
                ->groupBy('product_id')
                ->map(function($items, $productId) {
                    $product = $items->first()->product;
                    return [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'total_quantity' => $items->sum('quantity'),
                        'total_revenue' => $items->sum('total_amount'),
                        'total_orders' => $items->pluck('pos_id')->unique()->count()
                    ];
                })
                ->sortByDesc('total_revenue')
                ->take(20)
                ->values();

            return Inertia::render('Pos/Reports/Products', [
                'productData' => $productData,
            ]);
        }else{
            return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }

    public function customers(Request $request)
    {
        if(Auth::user()->can('manage-pos-reports')){
            $creatorId = creatorId();
            
            // Customer analysis data
            $customerData = PosItem::where('created_by', $creatorId)
                ->with('sale.customer')
                ->get()
                ->groupBy('sale.customer_id')
                ->map(function($items, $customerId) {
                    $customer = $items->first()->sale->customer;
                    $totalSpent = $items->sum('total_amount');
                    $orderCount = $items->pluck('pos_id')->unique()->count();
                    return [
                        'customer_id' => $customerId,
                        'customer' => ['name' => $customer->name ?? 'Walk-in'],
                        'total_orders' => $orderCount,
                        'total_spent' => $totalSpent,
                        'avg_order_value' => $orderCount > 0 ? $totalSpent / $orderCount : 0,
                        'last_order_date' => $items->max('created_at')
                    ];
                })
                ->sortByDesc('total_spent')
                ->take(20)
                ->values();

            return Inertia::render('Pos/Reports/Customers', [
                'customerData' => $customerData,
            ]);
        }else{
            return redirect()->route('pos.index')->with('error', __('Permission denied'));
        }
    }


}