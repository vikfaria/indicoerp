<?php

namespace Workdo\Account\Http\Controllers;

use App\Models\SalesInvoiceReturn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Account\Models\Customer;
use Workdo\Account\Models\Vendor;
use Workdo\Account\Models\CustomerPayment;
use Workdo\Account\Models\VendorPayment;
use Workdo\Account\Models\Revenue;
use Workdo\Account\Models\Expense;
use Carbon\Carbon;

class DashboardController extends Controller
{
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
        $creatorId = creatorId();

        $totalClients = Customer::where('created_by', $creatorId)->count();
        $totalVendors = Vendor::where('created_by', $creatorId)->count();
        $totalRevenue = Revenue::where('created_by', $creatorId)->sum('amount');
        $totalExpense = Expense::where('created_by', $creatorId)->sum('amount');
        $totalCustomerPayments = CustomerPayment::whereHas('customer', function($q) use ($creatorId) {
            $q->where('created_by', $creatorId);
        })->sum('payment_amount');
        $totalVendorPayments = VendorPayment::whereHas('vendor', function($q) use ($creatorId) {
            $q->where('created_by', $creatorId);
        })->sum('payment_amount');
        
        $netProfit = $totalRevenue - $totalExpense;

        $recentRevenues = Revenue::where('created_by', $creatorId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->revenue_number,
                    'description' => $item->description ?? 'Revenue transaction',
                    'amount' => $item->amount,
                    'date' => $item->created_at
                ];
            });

        $recentExpenses = Expense::where('created_by', $creatorId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->expense_number,
                    'description' => $item->description ?? 'Expense transaction',
                    'amount' => $item->amount,
                    'date' => $item->created_at
                ];
            });

        $isDemo = config('app.is_demo');
        $monthlyCustomerPayments = [];
        $monthlyVendorPayments = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            
            if ($isDemo) {
                $customerPayments = rand(15000, 45000) + rand(0, 99) / 100;
                $vendorPayments = rand(5000, 25000) + rand(0, 99) / 100;
            } else {
                $customerPayments = CustomerPayment::whereHas('customer', function($q) use ($creatorId) {
                    $q->where('created_by', $creatorId);
                })
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('payment_amount');
                
                $vendorPayments = VendorPayment::whereHas('vendor', function($q) use ($creatorId) {
                    $q->where('created_by', $creatorId);
                })
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('payment_amount');
            }
            
            $monthlyCustomerPayments[] = [
                'month' => $monthName,
                'customer_payments' => $customerPayments
            ];
            
            $monthlyVendorPayments[] = [
                'month' => $monthName,
                'vendor_payments' => $vendorPayments
            ];
        }

        return Inertia::render('Account/Dashboard/CompanyDashboard', [
            'stats' => [
                'total_clients' => $totalClients,
                'total_vendors' => $totalVendors,
                'total_revenue' => $totalRevenue,
                'total_expense' => $totalExpense,
                'total_customer_payment' => $totalCustomerPayments,
                'total_vendor_payment' => $totalVendorPayments,
                'net_profit' => $netProfit
            ],
            'monthlyCustomerPayments' => $monthlyCustomerPayments,
            'monthlyVendorPayments' => $monthlyVendorPayments,
            'recentRevenues' => $recentRevenues,
            'recentExpenses' => $recentExpenses
        ]);
    }

    private function vendorDashboard()
    {
        $user = Auth::user();

        $totalPayments = VendorPayment::where('vendor_id', $user->id)->sum('payment_amount');
        $totalExpenses = Expense::where('created_by', $user->created_by)->sum('amount');
        $paymentCount = VendorPayment::where('vendor_id', $user->id)->count();

        $isDemo = config('app.is_demo');
        $monthlyPayments = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');

            if ($isDemo) {
                $monthPayments = rand(1000, 10000) + rand(0, 99) / 100;
            } else {
                $monthPayments = VendorPayment::where('vendor_id', $user->id)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('payment_amount');
            }

            $monthlyPayments[] = [
                'month' => $monthName,
                'payments' => $monthPayments
            ];
        }

        // Dynamic return purchase invoices
        $recentReturnInvoices = collect();
        if (class_exists('\\App\Models\\PurchaseReturn')) {
            $recentReturnInvoices = \App\Models\PurchaseReturn::where('vendor_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($return) {
                    return [
                        'id' => $return->id,
                        'invoice_number' => $return->return_number ?? 'PUR-RET-' . $return->id,
                        'amount' => $return->total_amount ?? 0,
                        'date' => $return->created_at->format('M d, Y'),
                        'status' => $return->status ?? 'Pending'
                    ];
                });
        }

        // Dynamic debit notes
        $recentDebitNotes = collect();
        if (class_exists('\\Workdo\\Account\\Models\\DebitNote')) {
            $recentDebitNotes = \Workdo\Account\Models\DebitNote::where('vendor_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($note) {
                    return [
                        'id' => $note->id,
                        'debit_note_number' => $note->debit_note_number ?? 'DN-' . $note->id,
                        'amount' => $note->total_amount ?? 0,
                        'date' => $note->created_at->format('M d, Y'),
                        'status' => $note->status ?? 'Pending'
                    ];
                });
        }

        return Inertia::render('Account/Dashboard/VendorDashboard', [
            'stats' => [
                'total_payments' => $totalPayments,
                'total_expenses' => $totalExpenses,
                'payment_count' => $paymentCount
            ],
            'monthlyPayments' => $monthlyPayments,
            'recentReturnInvoices' => $recentReturnInvoices,
            'recentDebitNotes' => $recentDebitNotes,
            'vendor' => ['name' => $user->name]
        ]);
    }

    private function clientDashboard()
    {
        $user = Auth::user();

        $totalPayments = CustomerPayment::where('customer_id', $user->id)->sum('payment_amount');
        $totalRevenues = Revenue::where('created_by', $user->created_by)->sum('amount');
        $paymentCount = CustomerPayment::where('customer_id', $user->id)->count();

        $isDemo = config('app.is_demo');
        $monthlyPayments = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');

            if ($isDemo) {
                $monthPayments = rand(2000, 15000) + rand(0, 99) / 100;
            } else {
                $monthPayments = CustomerPayment::where('customer_id', $user->id)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('payment_amount');
            }

            $monthlyPayments[] = [
                'month' => $monthName,
                'payments' => $monthPayments
            ];
        }

        // Dynamic return invoices from SalesReturns
        $recentReturnInvoices = collect();
        if (class_exists('\\App\Models\\SalesInvoiceReturn')) {
            $recentReturnInvoices = SalesInvoiceReturn::where('customer_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($return) {
                    return [
                        'id' => $return->id,
                        'invoice_number' => $return->return_number ?? 'RET-' . $return->id,
                        'amount' => $return->total_amount ?? 0,
                        'date' => $return->created_at->format('M d, Y'),
                        'status' => $return->status ?? 'Pending'
                    ];
                });
        }

        // Dynamic credit notes
        $recentCreditNotes = collect();
        if (class_exists('\\Workdo\\Account\\Models\\CreditNote')) {
            $recentCreditNotes = \Workdo\Account\Models\CreditNote::where('customer_id', $user->id)
                ->latest()
                ->limit(5)
                ->get()
                ->map(function($note) {
                    return [
                        'id' => $note->id,
                        'credit_note_number' => $note->credit_note_number ?? 'CN-' . $note->id,
                        'amount' => $note->total_amount ?? 0,
                        'date' => $note->created_at->format('M d, Y'),
                        'status' => $note->status ?? 'Pending'
                    ];
                });
        }

        return Inertia::render('Account/Dashboard/ClientDashboard', [
            'stats' => [
                'total_payments' => $totalPayments,
                'total_revenues' => $totalRevenues,
                'payment_count' => $paymentCount
            ],
            'monthlyPayments' => $monthlyPayments,
            'recentReturnInvoices' => $recentReturnInvoices,
            'recentCreditNotes' => $recentCreditNotes,
            'customer' => ['name' => $user->name]
        ]);
    }

    private function staffDashboard()
    {
        $user = Auth::user();
        $creatorId = $user->created_by;

        $totalClients = Customer::where('created_by', $creatorId)->count();
        $totalVendors = Vendor::where('created_by', $creatorId)->count();
        $monthlyRevenue = Revenue::where('created_by', $creatorId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');
        $monthlyExpense = Expense::where('created_by', $creatorId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');

        $recentActivities = collect()
            ->merge(Revenue::where('created_by', $creatorId)->latest()->limit(3)->get()->map(function($item) {
                return ['type' => 'Revenue', 'title' => $item->revenue_number, 'amount' => $item->amount, 'date' => $item->created_at];
            }))
            ->merge(Expense::where('created_by', $creatorId)->latest()->limit(3)->get()->map(function($item) {
                return ['type' => 'Expense', 'title' => $item->expense_number, 'amount' => $item->amount, 'date' => $item->created_at];
            }))
            ->sortByDesc('date')
            ->take(6)
            ->values();

        return Inertia::render('Account/Dashboard/StaffDashboard', [
            'stats' => [
                'total_clients' => $totalClients,
                'total_vendors' => $totalVendors,
                'monthly_revenue' => $monthlyRevenue,
                'monthly_expense' => $monthlyExpense
            ],
            'recentActivities' => $recentActivities
        ]);
    }
}
