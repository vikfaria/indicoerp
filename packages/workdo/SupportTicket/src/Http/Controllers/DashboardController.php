<?php

namespace Workdo\SupportTicket\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Workdo\SupportTicket\Models\Ticket;
use Workdo\SupportTicket\Models\TicketCategory;
use Workdo\SupportTicket\Models\KnowledgeBase;
use Workdo\SupportTicket\Models\Faq;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (Auth::user()->can('manage-dashboard-support-ticket')) {

            if ($user->type == 'client') {
                return redirect()->route('dashboard.support-tickets.client');
            } elseif ($user->type == 'vendor') {
                return redirect()->route('dashboard.support-tickets.vendor');
            } elseif ($user->type == 'staff') {
                return redirect()->route('dashboard.support-tickets.staff');
            }

            $stats = $this->getStats(creatorId());
            $chartData = $this->getCategoryChartData();
            $monthlyData = $this->getMonthlyData(creatorId());

            return Inertia::render('SupportTicket/Dashboard/Index', [
                'stats' => $stats,
                'chartData' => $chartData,
                'monthlyData' => $monthlyData,
                'recentTickets' => $this->getRecentTickets(creatorId()),
                'statusData' => $this->getStatusData(creatorId()),
                'slug' => $this->getCompanySlug()
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function clientDashboard()
    {
        $user = Auth::user();
        if (Auth::user()->can('manage-dashboard-support-ticket')) {
            $stats = $this->getClientStats($user->id);
            $monthlyData = $this->getClientMonthlyData($user->id);
            $recentTickets = $this->getClientRecentTickets($user->id);
            $statusData = $this->getClientStatusData($user->id);

            return Inertia::render('SupportTicket/Dashboard/ClientDashboard', [
                'stats' => $stats,
                'monthlyData' => $monthlyData,
                'recentTickets' => $recentTickets,
                'statusData' => $statusData,
                'slug' => $this->getCompanySlug()
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function vendorDashboard()
    {
        $user = Auth::user();
        if (Auth::user()->can('manage-dashboard-support-ticket')) {
            $stats = $this->getVendorStats($user->id);
            $monthlyData = $this->getVendorMonthlyData($user->id);
            $recentTickets = $this->getVendorRecentTickets($user->id);
            $statusData = $this->getVendorStatusData($user->id);

            return Inertia::render('SupportTicket/Dashboard/VendorDashboard', [
                'stats' => $stats,
                'monthlyData' => $monthlyData,
                'recentTickets' => $recentTickets,
                'statusData' => $statusData,
                'slug' => $this->getCompanySlug()
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function staffDashboard()
    {
        $user = Auth::user();
        if (Auth::user()->can('manage-dashboard-support-ticket')) {
            $stats = $this->getStaffStats($user->id);
            $monthlyData = $this->getStaffMonthlyData($user->id);
            $recentTickets = $this->getStaffRecentTickets($user->id);
            $statusData = $this->getStaffStatusData($user->id);

            return Inertia::render('SupportTicket/Dashboard/StaffDashboard', [
                'stats' => $stats,
                'monthlyData' => $monthlyData,
                'recentTickets' => $recentTickets,
                'statusData' => $statusData,
                'slug' => $this->getCompanySlug()
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function getStats($createdBy)
    {
        $ticketStats = Ticket::where('created_by', $createdBy)
            ->selectRaw('
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = "Closed" THEN 1 ELSE 0 END) as closed_tickets,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_tickets,
                AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_response_time
            ')
            ->first();

        $otherStats = DB::table('ticket_categories')
            ->selectRaw('
                (SELECT COUNT(*) FROM ticket_categories WHERE created_by = ?) as categories,
                (SELECT COUNT(*) FROM support_ticket_knowledge_bases WHERE created_by = ?) as knowledge_base,
                (SELECT COUNT(*) FROM support_ticket_faqs WHERE created_by = ?) as faqs
            ', [$createdBy, $createdBy, $createdBy])
            ->first();

        $totalTickets = $ticketStats->total_tickets;
        $categories = $otherStats->categories;
        $openTickets = $ticketStats->open_tickets;
        $closedTickets = $ticketStats->closed_tickets;
        $todayTickets = $ticketStats->today_tickets;
        $avgResponseTime = $ticketStats->avg_response_time;

        $isDemo = config('app.is_demo');
        if ($isDemo) {
            if ($totalTickets == 0) $totalTickets = rand(25, 50);
            if ($categories == 0) $categories = rand(8, 15);
            if ($openTickets == 0) $openTickets = rand(5, 12);
            if ($closedTickets == 0) $closedTickets = rand(15, 35);
            if ($todayTickets == 0) $todayTickets = rand(2, 6);
            
            // If response time is 0 or unrealistically high, provide a professional demo value
            if (!$avgResponseTime || $avgResponseTime == 0 || $avgResponseTime > 100) {
                $avgResponseTime = rand(8, 24) + rand(0, 9) / 10;
            }
        }

        return [
            'totalTickets' => $totalTickets,
            'categories' => $categories,
            'openTickets' => $openTickets,
            'closedTickets' => $closedTickets,
            'todayTickets' => $todayTickets,
            'avgResponseTime' => round($avgResponseTime ?? 0, 1),
            'resolutionRate' => $totalTickets > 0 ? round(($closedTickets / $totalTickets) * 100, 1) : 0,
            'knowledgeBase' => $otherStats->knowledge_base,
            'faqs' => $otherStats->faqs,
        ];
    }

    private function getUserStats($userId)
    {
        return [
            'myTickets' => Ticket::where('user_id', $userId)->count(),
            'openTickets' => Ticket::where('user_id', $userId)->where('status', 'In Progress')->count(),
            'closedTickets' => Ticket::where('user_id', $userId)->where('status', 'Closed')->count(),
            'knowledgeBase' => KnowledgeBase::where('created_by', creatorId())->count(),
            'faqs' => Faq::where('created_by', creatorId())->count(),
        ];
    }

    private function getMonthlyData($createdBy)
    {
        $monthlyData = Ticket::select([
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as total'),
        ])
            ->where('created_at', '>', DB::raw('DATE_SUB(NOW(),INTERVAL 1 YEAR)'))
            ->where('created_by', $createdBy)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month')
            ->toArray();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Volatile professional trend: Business growth with realistic ups and downs
        $demoTrend = [45, 38, 52, 44, 60, 55, 72, 68, 85, 78, 95, 88];
        
        $result = [];
        $isDemo = config('app.is_demo');

        foreach ($months as $index => $month) {
            $val = $monthlyData[$index + 1] ?? 0;
            // In demo mode, if data is too low, override with volatile trend values
            if ($isDemo && $val < 15) {
                $val = $demoTrend[$index] + rand(-8, 8);
            }
            $result[$month] = $val;
        }
        return $result;
    }

    private function getCategoryChartData()
    {
        $categoriesChart = Ticket::select([
            'ticket_categories.name',
            'ticket_categories.color',
            DB::raw('count(*) as total'),
        ])
            ->join('ticket_categories', 'ticket_categories.id', '=', 'tickets.category')
            ->where('tickets.created_by', creatorId())
            ->groupBy('ticket_categories.id', 'ticket_categories.name', 'ticket_categories.color')
            ->orderBy('total', 'desc')
            ->get();

        $isDemo = config('app.is_demo');

        if (count($categoriesChart) > 0) {
            $defaultColors = ['#3B82F6', '#10b77f', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4', '#84CC16', '#F97316'];

            return $categoriesChart->map(function ($category, $index) use ($defaultColors) {
                return [
                    'name' => $category->name,
                    'value' => $category->total,
                    'color' => $category->color ?: ($defaultColors[$index % count($defaultColors)])
                ];
            })->toArray();
        }

        if ($isDemo) {
            return [
                ['name' => 'Technical Support', 'value' => rand(15, 30), 'color' => '#3B82F6'],
                ['name' => 'Billing Inquiry', 'value' => rand(10, 20), 'color' => '#10b77f'],
                ['name' => 'Feature Request', 'value' => rand(5, 15), 'color' => '#F59E0B'],
                ['name' => 'Bug Report', 'value' => rand(10, 25), 'color' => '#EF4444'],
            ];
        }

        return [['name' => 'No Data', 'value' => 1, 'color' => '#e3e3e3']];
    }

    private function getRecentTickets($createdBy)
    {
        return Ticket::where('created_by', $createdBy)
            ->with(['tcategory:id,name,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'ticket_id', 'name', 'email', 'subject', 'status', 'category', 'created_at'])
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->ticket_id,
                    'name' => $ticket->name,
                    'email' => $ticket->email,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'category' => $ticket->tcategory ? $ticket->tcategory->name : 'No Category',
                    'created_at' => $ticket->created_at->format('M d, Y H:i')
                ];
            });
    }

    private function getStatusData($createdBy)
    {
        $statusData = Ticket::select('status', DB::raw('count(*) as count'))
            ->where('created_by', $createdBy)
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $isDemo = config('app.is_demo');
        if ($isDemo && empty($statusData)) {
            $statusData = [
                'In Progress' => rand(15, 25),
                'On Hold' => rand(5, 10),
                'Closed' => rand(25, 45)
            ];
        }

        $colors = [
            'In Progress' => '#F59E0B',
            'On Hold' => '#EF4444',
            'Closed' => '#10b77f'
        ];

        $result = [];
        foreach ($statusData as $status => $count) {
            $result[] = [
                'name' => $status,
                'value' => $count,
                'color' => $colors[$status] ?? '#6B7280'
            ];
        }

        return $result;
    }

    private function getClientStats($userId)
    {
        return $this->getUserTicketStats($userId);
    }

    private function getVendorStats($userId)
    {
        return $this->getUserTicketStats($userId);
    }

    private function getUserTicketStats($userId)
    {
        $stats = Ticket::where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_tickets,
                SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = "Closed" THEN 1 ELSE 0 END) as closed_tickets,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_tickets
            ')
            ->first();

        return [
            'totalTickets' => $stats->total_tickets,
            'openTickets' => $stats->open_tickets,
            'closedTickets' => $stats->closed_tickets,
            'todayTickets' => $stats->today_tickets,
            'resolutionRate' => $stats->total_tickets > 0 ? round(($stats->closed_tickets / $stats->total_tickets) * 100, 1) : 0,
        ];
    }

    private function getStaffStats($userId)
    {
        $user = Auth::user();
        $canViewTickets = $user->can('view-support-tickets');

        if ($canViewTickets) {
            $query = Ticket::where(function ($q) use ($user) {
                if ($user->can('manage-any-support-tickets')) {
                    $q->where('created_by', $user->created_by);
                } elseif ($user->can('manage-own-support-tickets')) {
                    $q->where(function ($subQ) use ($user) {
                        $subQ->where('creator_id', Auth::id())
                            ->orWhere('user_id', Auth::id());
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            $totalTickets = $query->count();
            $openTickets = (clone $query)->where('status', 'In Progress')->count();
            $closedTickets = (clone $query)->where('status', 'Closed')->count();
            $todayTickets = (clone $query)->whereDate('created_at', today())->count();
        } else {
            $totalTickets = $openTickets = $closedTickets = $todayTickets = 0;
        }

        return [
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'closedTickets' => $closedTickets,
            'todayTickets' => $todayTickets,
            'resolutionRate' => $totalTickets > 0 ? round(($closedTickets / $totalTickets) * 100, 1) : 0,
            'canViewTickets' => $canViewTickets,
        ];
    }

    private function getClientMonthlyData($userId)
    {
        return $this->getMonthlyDataByUser($userId, 'user_id');
    }

    private function getVendorMonthlyData($userId)
    {
        return $this->getMonthlyDataByUser($userId, 'user_id');
    }

    private function getStaffMonthlyData($userId)
    {
        $user = Auth::user();
        if ($user->can('view-support-tickets')) {
            if ($user->can('manage-any-support-tickets')) {
                return $this->getMonthlyData($user->created_by);
            } elseif ($user->can('manage-own-support-tickets')) {
                return $this->getStaffMonthlyDataByUser(Auth::id());
            }
        }
        return array_fill_keys(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 0);
    }

    private function getMonthlyDataByUser($userId, $field)
    {
        $barChart = Ticket::select([
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as total'),
        ])
            ->where('created_at', '>', DB::raw('DATE_SUB(NOW(),INTERVAL 1 YEAR)'))
            ->where($field, $userId)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Volatile demo trend: Low volume but with natural business 'pulses'
        $demoTrend = [15, 12, 18, 14, 22, 19, 28, 25, 32, 28, 38, 34];
        
        $monthlyData = [];
        $isDemo = config('app.is_demo');

        foreach ($months as $index => $month) {
            $monthlyData[$month] = 0;
            foreach ($barChart as $chart) {
                if (intval($chart->month) == ($index + 1)) {
                    $monthlyData[$month] = $chart->total;
                }
            }

            if ($isDemo && $monthlyData[$month] < 5) {
                $monthlyData[$month] = $demoTrend[$index] + rand(-4, 4);
            }
        }
        return $monthlyData;
    }

    private function getClientRecentTickets($userId)
    {
        return $this->getRecentTicketsByUser($userId, 'user_id');
    }

    private function getVendorRecentTickets($userId)
    {
        return $this->getRecentTicketsByUser($userId, 'user_id');
    }

    private function getStaffRecentTickets($userId)
    {
        $user = Auth::user();
        if ($user->can('view-support-tickets')) {
            if ($user->can('manage-any-support-tickets')) {
                return $this->getRecentTickets($user->created_by);
            } elseif ($user->can('manage-own-support-tickets')) {
                return $this->getStaffRecentTicketsByUser(Auth::id());
            }
        }
        return [];
    }

    private function getRecentTicketsByUser($userId, $field)
    {
        return Ticket::where($field, $userId)
            ->with(['tcategory:id,name,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'ticket_id', 'name', 'email', 'subject', 'status', 'category', 'created_at'])
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->ticket_id,
                    'name' => $ticket->name,
                    'email' => $ticket->email,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'category' => $ticket->tcategory ? $ticket->tcategory->name : 'No Category',
                    'created_at' => $ticket->created_at->format('M d, Y H:i')
                ];
            });
    }

    private function getClientStatusData($userId)
    {
        return $this->getStatusDataByUser($userId, 'user_id');
    }

    private function getVendorStatusData($userId)
    {
        return $this->getStatusDataByUser($userId, 'user_id');
    }

    private function getStaffStatusData($userId)
    {
        $user = Auth::user();
        if ($user->can('view-support-tickets')) {
            if ($user->can('manage-any-support-tickets')) {
                return $this->getStatusData($user->created_by);
            } elseif ($user->can('manage-own-support-tickets')) {
                return $this->getStaffStatusDataByUser(Auth::id());
            }
        }
        return [];
    }

    private function getStatusDataByUser($userId, $field)
    {
        $statusData = Ticket::select('status', DB::raw('count(*) as count'))
            ->where($field, $userId)
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $isDemo = config('app.is_demo');
        if ($isDemo && empty($statusData)) {
            $statusData = [
                'In Progress' => rand(3, 8),
                'On Hold' => rand(1, 3),
                'Closed' => rand(5, 12)
            ];
        }

        $colors = [
            'In Progress' => '#F59E0B',
            'On Hold' => '#EF4444',
            'Closed' => '#10b77f'
        ];

        $result = [];
        foreach ($statusData as $status => $count) {
            $result[] = [
                'name' => $status,
                'value' => $count,
                'color' => $colors[$status] ?? '#6B7280'
            ];
        }

        return $result;
    }

    private function getStaffMonthlyDataByUser($userId)
    {
        $barChart = Ticket::select([
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as total'),
        ])
            ->where('created_at', '>', DB::raw('DATE_SUB(NOW(),INTERVAL 1 YEAR)'))
            ->where(function ($q) use ($userId) {
                $q->where('creator_id', $userId)
                    ->orWhere('user_id', $userId);
            })
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Volatile demo trend: Intermediate ticket volume with clear spikes/dips
        $demoTrend = [22, 18, 28, 24, 35, 30, 42, 38, 50, 45, 60, 54];
        
        $monthlyData = [];
        $isDemo = config('app.is_demo');

        foreach ($months as $index => $month) {
            $monthlyData[$month] = 0;
            foreach ($barChart as $chart) {
                if (intval($chart->month) == ($index + 1)) {
                    $monthlyData[$month] = $chart->total;
                }
            }

            if ($isDemo && $monthlyData[$month] < 8) {
                $monthlyData[$month] = $demoTrend[$index] + rand(-5, 5);
            }
        }
        return $monthlyData;
    }

    private function getStaffRecentTicketsByUser($userId)
    {
        return Ticket::where(function ($q) use ($userId) {
            $q->where('creator_id', $userId)
                ->orWhere('user_id', $userId);
        })
            ->with(['tcategory:id,name,color'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'ticket_id', 'name', 'email', 'subject', 'status', 'category', 'created_at'])
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->ticket_id,
                    'name' => $ticket->name,
                    'email' => $ticket->email,
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'category' => $ticket->tcategory ? $ticket->tcategory->name : 'No Category',
                    'created_at' => $ticket->created_at->format('M d, Y H:i')
                ];
            });
    }

    private function getStaffStatusDataByUser($userId)
    {
        $statusData = Ticket::select('status', DB::raw('count(*) as count'))
            ->where(function ($q) use ($userId) {
                $q->where('creator_id', $userId)
                    ->orWhere('user_id', $userId);
            })
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $isDemo = config('app.is_demo');
        if ($isDemo && empty($statusData)) {
            $statusData = [
                'In Progress' => rand(8, 15),
                'On Hold' => rand(2, 6),
                'Closed' => rand(15, 30)
            ];
        }

        $colors = [
            'In Progress' => '#F59E0B',
            'On Hold' => '#EF4444',
            'Closed' => '#10b77f'
        ];

        $result = [];
        foreach ($statusData as $status => $count) {
            $result[] = [
                'name' => $status,
                'value' => $count,
                'color' => $colors[$status] ?? '#6B7280'
            ];
        }

        return $result;
    }

    private function getCompanySlug()
    {
        $user = Auth::user();
        return $user->type === 'company' ? $user->slug : (User::find($user->created_by)->slug ?? 'demo');
    }
}