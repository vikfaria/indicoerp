<?php

namespace Workdo\Account\Http\Controllers;

use App\Models\SalesInvoiceReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\Expense;
use Workdo\Account\Models\Revenue;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Services\AccountCacheService;

class DashboardController extends Controller
{
    private const DASHBOARD_CACHE_VERSION = 'v1';

    public function index(Request $request)
    {
        if(Auth::user()->can('manage-account-dashboard')){
            $user = Auth::user();
            $userType = $user->type;

            switch ($userType) {
                case 'company':
                    return $this->companyDashboard();
                case 'vendor':
                    return $this->vendorDashboard();
                case 'client':
                    return $this->clientDashboard();
                case 'staff':
                default:
                    return $this->staffDashboard();
            }
        }
        return back()->with('error', __('Permission denied'));
    }

    private function companyDashboard()
    {
        $creatorId = (int) creatorId();
        $payload = $this->rememberDashboardPayload($creatorId, "company:{$creatorId}", function () use ($creatorId) {
            $totalClients = Customer::where('created_by', $creatorId)->count();
            $totalVendors = Vendor::where('created_by', $creatorId)->count();
            $totalRevenue = (float) Revenue::where('created_by', $creatorId)->sum('amount');
            $totalExpense = (float) Expense::where('created_by', $creatorId)->sum('amount');
            $totalCustomerPayments = (float) CustomerPayment::where('created_by', $creatorId)->sum('payment_amount');
            $totalVendorPayments = (float) VendorPayment::where('created_by', $creatorId)->sum('payment_amount');
            $netProfit = $totalRevenue - $totalExpense;

            $recentRevenues = Revenue::where('created_by', $creatorId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->revenue_number,
                        'description' => $item->description ?? 'Revenue transaction',
                        'amount' => (float) $item->amount,
                        'date' => optional($item->created_at)->toDateTimeString(),
                    ];
                })
                ->values()
                ->all();

            $recentExpenses = Expense::where('created_by', $creatorId)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->expense_number,
                        'description' => $item->description ?? 'Expense transaction',
                        'amount' => (float) $item->amount,
                        'date' => optional($item->created_at)->toDateTimeString(),
                    ];
                })
                ->values()
                ->all();

            $months = $this->recentMonthDescriptors();
            if (config('app.is_demo')) {
                $monthlyCustomerPayments = collect($months)->map(function (array $month) {
                    return [
                        'month' => $month['label'],
                        'customer_payments' => rand(15000, 45000) + rand(0, 99) / 100,
                    ];
                })->values()->all();

                $monthlyVendorPayments = collect($months)->map(function (array $month) {
                    return [
                        'month' => $month['label'],
                        'vendor_payments' => rand(5000, 25000) + rand(0, 99) / 100,
                    ];
                })->values()->all();
            } else {
                $customerTotalsByPeriod = $this->resolveMonthlyTotals(
                    'customer_payments',
                    'payment_amount',
                    $months,
                    ['created_by' => $creatorId]
                );

                $vendorTotalsByPeriod = $this->resolveMonthlyTotals(
                    'vendor_payments',
                    'payment_amount',
                    $months,
                    ['created_by' => $creatorId]
                );

                $monthlyCustomerPayments = $this->buildMonthlySeries(
                    $months,
                    $customerTotalsByPeriod,
                    'customer_payments'
                );
                $monthlyVendorPayments = $this->buildMonthlySeries(
                    $months,
                    $vendorTotalsByPeriod,
                    'vendor_payments'
                );
            }

            return [
                'stats' => [
                    'total_clients' => $totalClients,
                    'total_vendors' => $totalVendors,
                    'total_revenue' => $totalRevenue,
                    'total_expense' => $totalExpense,
                    'total_customer_payment' => $totalCustomerPayments,
                    'total_vendor_payment' => $totalVendorPayments,
                    'net_profit' => $netProfit,
                ],
                'monthlyCustomerPayments' => $monthlyCustomerPayments,
                'monthlyVendorPayments' => $monthlyVendorPayments,
                'recentRevenues' => $recentRevenues,
                'recentExpenses' => $recentExpenses,
            ];
        });

        return Inertia::render('Account/Dashboard/CompanyDashboard', $payload);
    }

    private function vendorDashboard()
    {
        $user = Auth::user();
        $vendorId = (int) $user->id;
        $companyId = (int) $user->created_by;
        $payload = $this->rememberDashboardPayload($companyId, "vendor:{$vendorId}:{$companyId}", function () use ($vendorId, $companyId, $user) {
            $totalPayments = (float) VendorPayment::where('vendor_id', $vendorId)->sum('payment_amount');
            $totalExpenses = (float) Expense::where('created_by', $companyId)->sum('amount');
            $paymentCount = VendorPayment::where('vendor_id', $vendorId)->count();

            $months = $this->recentMonthDescriptors();
            if (config('app.is_demo')) {
                $monthlyPayments = collect($months)->map(function (array $month) {
                    return [
                        'month' => $month['label'],
                        'payments' => rand(1000, 10000) + rand(0, 99) / 100,
                    ];
                })->values()->all();
            } else {
                $totalsByPeriod = $this->resolveMonthlyTotals(
                    'vendor_payments',
                    'payment_amount',
                    $months,
                    ['vendor_id' => $vendorId]
                );
                $monthlyPayments = $this->buildMonthlySeries($months, $totalsByPeriod, 'payments');
            }

            $recentReturnInvoices = [];
            if (class_exists('\\App\\Models\\PurchaseReturn')) {
                $recentReturnInvoices = \App\Models\PurchaseReturn::where('vendor_id', $vendorId)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($return) {
                        return [
                            'id' => $return->id,
                            'invoice_number' => $return->return_number ?? 'PUR-RET-' . $return->id,
                            'amount' => (float) ($return->total_amount ?? 0),
                            'date' => optional($return->created_at)->format('M d, Y'),
                            'status' => $return->status ?? 'Pending',
                        ];
                    })
                    ->values()
                    ->all();
            }

            $recentDebitNotes = [];
            if (class_exists('\\Workdo\\Account\\Models\\DebitNote')) {
                $recentDebitNotes = \Workdo\Account\Models\DebitNote::where('vendor_id', $vendorId)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'debit_note_number' => $note->debit_note_number ?? 'DN-' . $note->id,
                            'amount' => (float) ($note->total_amount ?? 0),
                            'date' => optional($note->created_at)->format('M d, Y'),
                            'status' => $note->status ?? 'Pending',
                        ];
                    })
                    ->values()
                    ->all();
            }

            return [
                'stats' => [
                    'total_payments' => $totalPayments,
                    'total_expenses' => $totalExpenses,
                    'payment_count' => $paymentCount,
                ],
                'monthlyPayments' => $monthlyPayments,
                'recentReturnInvoices' => $recentReturnInvoices,
                'recentDebitNotes' => $recentDebitNotes,
                'vendor' => ['name' => $user->name],
            ];
        });

        return Inertia::render('Account/Dashboard/VendorDashboard', $payload);
    }

    private function clientDashboard()
    {
        $user = Auth::user();
        $customerId = (int) $user->id;
        $companyId = (int) $user->created_by;
        $payload = $this->rememberDashboardPayload($companyId, "client:{$customerId}:{$companyId}", function () use ($customerId, $companyId, $user) {
            $totalPayments = (float) CustomerPayment::where('customer_id', $customerId)->sum('payment_amount');
            $totalRevenues = (float) Revenue::where('created_by', $companyId)->sum('amount');
            $paymentCount = CustomerPayment::where('customer_id', $customerId)->count();

            $months = $this->recentMonthDescriptors();
            if (config('app.is_demo')) {
                $monthlyPayments = collect($months)->map(function (array $month) {
                    return [
                        'month' => $month['label'],
                        'payments' => rand(2000, 15000) + rand(0, 99) / 100,
                    ];
                })->values()->all();
            } else {
                $totalsByPeriod = $this->resolveMonthlyTotals(
                    'customer_payments',
                    'payment_amount',
                    $months,
                    ['customer_id' => $customerId]
                );
                $monthlyPayments = $this->buildMonthlySeries($months, $totalsByPeriod, 'payments');
            }

            $recentReturnInvoices = [];
            if (class_exists('\\App\\Models\\SalesInvoiceReturn')) {
                $recentReturnInvoices = SalesInvoiceReturn::where('customer_id', $customerId)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($return) {
                        return [
                            'id' => $return->id,
                            'invoice_number' => $return->return_number ?? 'RET-' . $return->id,
                            'amount' => (float) ($return->total_amount ?? 0),
                            'date' => optional($return->created_at)->format('M d, Y'),
                            'status' => $return->status ?? 'Pending',
                        ];
                    })
                    ->values()
                    ->all();
            }

            $recentCreditNotes = [];
            if (class_exists('\\Workdo\\Account\\Models\\CreditNote')) {
                $recentCreditNotes = \Workdo\Account\Models\CreditNote::where('customer_id', $customerId)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'credit_note_number' => $note->credit_note_number ?? 'CN-' . $note->id,
                            'amount' => (float) ($note->total_amount ?? 0),
                            'date' => optional($note->created_at)->format('M d, Y'),
                            'status' => $note->status ?? 'Pending',
                        ];
                    })
                    ->values()
                    ->all();
            }

            return [
                'stats' => [
                    'total_payments' => $totalPayments,
                    'total_revenues' => $totalRevenues,
                    'payment_count' => $paymentCount,
                ],
                'monthlyPayments' => $monthlyPayments,
                'recentReturnInvoices' => $recentReturnInvoices,
                'recentCreditNotes' => $recentCreditNotes,
                'customer' => ['name' => $user->name],
            ];
        });

        return Inertia::render('Account/Dashboard/ClientDashboard', $payload);
    }

    private function staffDashboard()
    {
        $user = Auth::user();
        $creatorId = (int) $user->created_by;
        $payload = $this->rememberDashboardPayload($creatorId, "staff:{$creatorId}", function () use ($creatorId) {
            $totalClients = Customer::where('created_by', $creatorId)->count();
            $totalVendors = Vendor::where('created_by', $creatorId)->count();
            $monthlyRevenue = (float) Revenue::where('created_by', $creatorId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount');
            $monthlyExpense = (float) Expense::where('created_by', $creatorId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('amount');

            $recentActivities = collect()
                ->merge(Revenue::where('created_by', $creatorId)->latest()->limit(3)->get()->map(function ($item) {
                    return [
                        'type' => 'Revenue',
                        'title' => $item->revenue_number,
                        'amount' => (float) $item->amount,
                        'date' => optional($item->created_at)->toDateTimeString(),
                    ];
                }))
                ->merge(Expense::where('created_by', $creatorId)->latest()->limit(3)->get()->map(function ($item) {
                    return [
                        'type' => 'Expense',
                        'title' => $item->expense_number,
                        'amount' => (float) $item->amount,
                        'date' => optional($item->created_at)->toDateTimeString(),
                    ];
                }))
                ->sortByDesc('date')
                ->take(6)
                ->values()
                ->all();

            return [
                'stats' => [
                    'total_clients' => $totalClients,
                    'total_vendors' => $totalVendors,
                    'monthly_revenue' => $monthlyRevenue,
                    'monthly_expense' => $monthlyExpense,
                ],
                'recentActivities' => $recentActivities,
            ];
        });

        return Inertia::render('Account/Dashboard/StaffDashboard', $payload);
    }

    private function rememberDashboardPayload(int $companyId, string $scope, callable $resolver): array
    {
        $cacheVersion = AccountCacheService::currentVersion($companyId);
        $cacheKey = sprintf(
            'account:dashboard:%s:cv%d:%s',
            self::DASHBOARD_CACHE_VERSION,
            $cacheVersion,
            $scope
        );

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($this->dashboardCacheTtlSeconds()),
            static fn () => $resolver()
        );
    }

    private function dashboardCacheTtlSeconds(): int
    {
        return max(30, (int) config('performance.dashboard_cache_ttl_seconds', 120));
    }

    private function recentMonthDescriptors(int $count = 6): array
    {
        $months = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'label' => $date->format('M'),
                'period' => $date->format('Y-m'),
            ];
        }

        return $months;
    }

    private function resolveMonthlyTotals(
        string $table,
        string $amountColumn,
        array $months,
        array $where = []
    ): array {
        if (empty($months)) {
            return [];
        }

        $start = Carbon::createFromFormat('Y-m', $months[0]['period'])->startOfMonth()->toDateTimeString();
        $end = Carbon::createFromFormat('Y-m', $months[count($months) - 1]['period'])->endOfMonth()->toDateTimeString();

        $query = DB::table($table)->whereBetween('created_at', [$start, $end]);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        return $query
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, COALESCE(SUM({$amountColumn}),0) as total")
            ->groupBy('period')
            ->pluck('total', 'period')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function buildMonthlySeries(array $months, array $totalsByPeriod, string $valueKey): array
    {
        return collect($months)->map(function (array $month) use ($totalsByPeriod, $valueKey) {
            return [
                'month' => $month['label'],
                $valueKey => (float) ($totalsByPeriod[$month['period']] ?? 0),
            ];
        })->values()->all();
    }
}
