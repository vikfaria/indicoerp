<?php

namespace Workdo\Pos\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Workdo\Pos\Models\Pos;
use Workdo\ProductService\Models\ProductServiceItem;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Pos\Models\PosPayment;
use Workdo\Pos\Models\PosItem;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-pos-dashboard')) {
            $user = Auth::user();
            $userType = $user->type;

            // Route to appropriate dashboard based on user type
            switch ($userType) {
                case 'company':
                    return $this->companyDashboard();
                case 'client':
                default:
                    return $this->clientDashboard();
            }
        }
    }

    private function companyDashboard()
    {
        $creatorId = creatorId();
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        // Sales Analytics
        $todaySales = PosPayment::where('created_by', $creatorId)
            ->whereDate('created_at', $today)
            ->sum('discount_amount');

        $weekSales = PosPayment::where('created_by', $creatorId)
            ->where('created_at', '>=', $thisWeek)
            ->sum('discount_amount');

        $monthSales = PosPayment::where('created_by', $creatorId)
            ->where('created_at', '>=', $thisMonth)
            ->sum('discount_amount');

        $totalSales = PosPayment::where('created_by', $creatorId)->count();
        $totalRevenue = PosPayment::where('created_by', $creatorId)->sum('discount_amount');

        $isDemo = config('app.is_demo');
        if ($isDemo) {
            if ($todaySales == 0) $todaySales = rand(500, 2000) + rand(0, 99) / 100;
            if ($weekSales == 0) $weekSales = rand(5000, 15000) + rand(0, 99) / 100;
            if ($monthSales == 0) $monthSales = rand(20000, 50000) + rand(0, 99) / 100;
            if ($totalSales == 0) $totalSales = rand(50, 150);
            if ($totalRevenue == 0) $totalRevenue = rand(100000, 500000) + rand(0, 99) / 100;
        }

        $avgTransaction = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Product Stats
        $totalProducts = ProductServiceItem::where('created_by', $creatorId)
            ->where('type', '!=', 'service')
            ->count();
        
        if ($isDemo && $totalProducts == 0) {
            $totalProducts = rand(20, 50);
        }

        // Top Products
        $topProducts = DB::table('pos_items')
            ->join('pos', 'pos_items.pos_id', '=', 'pos.id')
            ->join('product_service_items', 'pos_items.product_id', '=', 'product_service_items.id')
            ->where('pos.created_by', $creatorId)
            ->select(
                'product_service_items.name',
                DB::raw('SUM(pos_items.quantity) as total_quantity'),
                DB::raw('SUM(pos_items.total_amount) as total_revenue')
            )
            ->groupBy('pos_items.product_id', 'product_service_items.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();

        // Recent Sales
        $recentSales = Pos::with(['customer:id,name', 'warehouse:id,name', 'payment:pos_id,discount_amount'])
            ->where('created_by', $creatorId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                $sale->total = PosPayment::where('pos_id', $sale->id)->sum('discount_amount');
                return $sale;
            });

        // Sales by Status
        $salesByStatus = Pos::where('created_by', $creatorId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();



        // Customer Stats
        $totalCustomers = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->where('created_by', $creatorId)->count();

        if ($isDemo && $totalCustomers == 0) {
            $totalCustomers = rand(20, 100);
        }

        $walkInSales = Pos::where('created_by', $creatorId)
            ->whereNull('customer_id')
            ->count();

        // Last 10 days sales report
        $last10DaysSales = [];
        for ($i = 9; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailySales = DB::table('pos_payments')
                ->join('pos', 'pos_payments.pos_id', '=', 'pos.id')
                ->where('pos.created_by', $creatorId)
                ->whereDate('pos_payments.created_at', $date)
                ->sum('pos_payments.discount_amount');
            
            if ($isDemo && $dailySales == 0) {
                $dailySales = rand(1000, 5000) + rand(0, 99) / 100;
            }

            $last10DaysSales[] = [
                'date' => $date->format('M d'),
                'sales' => $dailySales
            ];
        }

        // Out of stock products warehouse wise
        $outOfStockProductsList = ProductServiceItem::where('created_by', $creatorId)
            ->where('type', '!=', 'service')
            ->with(['warehouseStocks.warehouse'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->flatMap(function ($product) {
                return $product->warehouseStocks
                    ->filter(function ($stock) {
                        return $stock->quantity == 0;
                    })
                    ->map(function ($stock) use ($product) {
                        return [
                            'product_name' => $product->name,
                            'sku' => $product->sku ?? 'N/A',
                            'warehouse_name' => $stock->warehouse->name ?? 'Unknown',
                            'stock' => $stock->quantity
                        ];
                    });
            })

            ->values();
        return Inertia::render('Pos/Dashboard/Index', [
            'stats' => [
                'today_sales' => $todaySales,
                'week_sales' => $weekSales,
                'month_sales' => $monthSales,
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'avg_transaction' => $avgTransaction,
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'walk_in_sales' => $walkInSales,
            ],
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'salesByStatus' => $salesByStatus,
            'last10DaysSales' => $last10DaysSales,
            'outOfStockProductsList' => $outOfStockProductsList,

        ]);
    }
    private function clientDashboard()
    {
        $userId = Auth::id();
        $creatorId = creatorId();

        // Client's purchase stats
        $clientSales = Pos::where('customer_id', $userId)
            ->where('created_by', $creatorId)
            ->with(['items.product'])
            ->get();

        $totalPurchases = $clientSales->count();
        $totalSpent = $clientSales->sum(function ($sale) {
            return $sale->items->sum('total_amount');
        });

        $isDemo = config('app.is_demo');
        if ($isDemo) {
            if ($totalPurchases == 0) $totalPurchases = rand(5, 15);
            if ($totalSpent == 0) $totalSpent = rand(5000, 15000) + rand(0, 99) / 100;
        }

        $avgOrderValue = $totalPurchases > 0 ? $totalSpent / $totalPurchases : 0;

        // Recent purchases
        $recentPurchases = Pos::where('customer_id', $userId)
            ->where('created_by', $creatorId)
            ->with(['warehouse:id,name', 'items.product:id,name,sku'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($sale) {
                $sale->total = $sale->items->sum('total_amount');
                return $sale;
            });

        // Purchased products
        $purchasedProducts = PosItem::whereHas('sale', function ($query) use ($userId, $creatorId) {
            $query->where('customer_id', $userId)->where('created_by', $creatorId);
        })
            ->with('product')
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'total_quantity' => $items->sum('quantity'),
                    'total_spent' => $items->sum('total_amount'),
                    'orders_count' => $items->pluck('pos_id')->unique()->count()
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        // Daily spending for last 10 days
        $monthlySpending = [];
        for ($i = 9; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $spending = PosItem::whereHas('sale', function ($query) use ($userId, $creatorId) {
                $query->where('customer_id', $userId)->where('created_by', $creatorId);
            })
                ->whereDate('created_at', $date)
                ->sum('total_amount');
            
            if ($isDemo && $spending == 0) {
                $spending = rand(100, 1000) + rand(0, 99) / 100;
            }

            $monthlySpending[] = [
                'month' => $date->format('M d'),
                'spending' => $spending
            ];
        }

        return Inertia::render('Pos/Dashboard/ClientDashboard', [
            'stats' => [
                'total_purchases' => $totalPurchases,
                'total_spent' => $totalSpent,
                'avg_order_value' => $avgOrderValue,
            ],
            'recentPurchases' => $recentPurchases,
            'purchasedProducts' => $purchasedProducts,
            'monthlySpending' => $monthlySpending,
        ]);
    }
}
