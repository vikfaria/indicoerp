<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function Dashboard(Request $request)
    {
        if(Auth::user()->type === 'superadmin') {
            return $this->superAdminDashboard();
        }

        return $this->regularDashboard();
    }

    private function superAdminDashboard()
    {
        $orderData = Order::selectRaw('MONTH(created_at) as month, COUNT(*) as count, SUM(price) as payments')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        $isDemo = config('app.is_demo');

        for ($i = 1; $i <= 12; $i++) {
            if ($isDemo) {
                $chartData[] = [
                    'month' => $months[$i-1],
                    'orders' => rand(5, 20),
                    'payments' => rand(500, 5000)
                ];
            } else {
                $chartData[] = [
                    'month' => $months[$i-1],
                    'orders' => $orderData[$i]->count ?? 0,
                    'payments' => $orderData[$i]->payments ?? 0
                ];
            }
        }

        return Inertia::render('SuperAdminDashboard', [
            'stats' => [
                'order_payments' => Order::sum('price') ?? 0,
                'total_orders' => Order::count(),
                'total_plans' => Plan::count(),
                'total_companies' => User::where('type', 'company')->count(),
            ],
            'chartData' => $chartData
        ]);
    }

    private function regularDashboard()
    {
        $packagesPath = base_path('packages/workdo');

        // find dashboard menu from all  active package and redirect if found
        if (is_dir($packagesPath)) {
            foreach (glob($packagesPath . '/*/src/Resources/js/menus/company-menu.ts') as $menuFile) {
                preg_match('/packages\/workdo\/([^\/]+)\//', $menuFile, $moduleMatch);
                $moduleName = $moduleMatch[1] ?? null;
                    $content = file_get_contents($menuFile);
                    if (preg_match("/parent:\s*['\"]dashboard['\"]/", $content)) {
                        preg_match("/href:\s*route\(['\"]([^'\"]+)['\"]/", $content, $routeMatch);
                        preg_match("/permission:\s*['\"]([^'\"]+)['\"]/", $content, $permMatch);
                        if (!empty($routeMatch[1]) && !empty($permMatch[1]) &&  Module_is_active($moduleName) && Auth::user()->can($permMatch[1])) {
                            return redirect()->route($routeMatch[1]);
                        }
                }
            }
        }

        return Inertia::render('dashboard');
    }
}
