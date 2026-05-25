<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function Dashboard(Request $request)
    {
        if (Auth::user()->isSuperAdminUser()) {
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
        $user = Auth::user();
        if (!$user) {
            return Inertia::render('dashboard');
        }

        $activatedModules = ActivatedModule($user->id);
        sort($activatedModules);

        $permissions = method_exists($user, 'getAllPermissions')
            ? Cache::remember('dashboard:user_permissions:' . $user->id, now()->addMinutes(10), function () use ($user) {
                return $user->getAllPermissions()->pluck('name')->sort()->values()->implode('|');
            })
            : '';

        $userRouteKey = 'dashboard:default_route:' . md5(
            $user->id . '|' . implode(',', $activatedModules) . '|' . $permissions
        );

        $cachedRoute = Cache::get($userRouteKey);
        if ($cachedRoute && Route::has($cachedRoute)) {
            return redirect()->route($cachedRoute);
        }

        foreach ($this->getDashboardMenuCandidates() as $candidate) {
            $moduleName = $candidate['module'] ?? null;
            $routeName = $candidate['route'] ?? null;
            $permission = $candidate['permission'] ?? null;

            if (!$routeName || !$permission || !Route::has($routeName)) {
                continue;
            }

            $moduleAllowed = !$moduleName || in_array($moduleName, $activatedModules, true);
            if ($moduleAllowed && $user->can($permission)) {
                Cache::put($userRouteKey, $routeName, now()->addMinutes(10));
                return redirect()->route($routeName);
            }
        }

        return Inertia::render('dashboard');
    }

    /**
     * Parse package menus once and cache dashboard route candidates.
     */
    private function getDashboardMenuCandidates(): array
    {
        return Cache::remember('dashboard:menu_candidates', now()->addMinutes(15), function () {
            $packagesPath = base_path('packages/workdo');
            if (!is_dir($packagesPath)) {
                return [];
            }

            $candidates = [];
            foreach (glob($packagesPath . '/*/src/Resources/js/menus/company-menu.ts') as $menuFile) {
                preg_match('/packages\/workdo\/([^\/]+)\//', $menuFile, $moduleMatch);
                $moduleName = $moduleMatch[1] ?? null;

                $content = @file_get_contents($menuFile);
                if ($content === false || !preg_match("/parent:\s*['\"]dashboard['\"]/", $content)) {
                    continue;
                }

                preg_match("/href:\s*route\(['\"]([^'\"]+)['\"]/", $content, $routeMatch);
                preg_match("/permission:\s*['\"]([^'\"]+)['\"]/", $content, $permMatch);
                if (!empty($routeMatch[1]) && !empty($permMatch[1])) {
                    $candidates[] = [
                        'module' => $moduleName,
                        'route' => $routeMatch[1],
                        'permission' => $permMatch[1],
                    ];
                }
            }

            return $candidates;
        });
    }
}
