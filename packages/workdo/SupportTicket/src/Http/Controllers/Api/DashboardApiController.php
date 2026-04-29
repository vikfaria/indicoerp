<?php

namespace Workdo\SupportTicket\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Workdo\SupportTicket\Models\Ticket;

class DashboardApiController extends Controller
{
    use ApiResponseTrait;
    public function index()
    {
        try {
            $user = Auth::user();

            if (Auth::user()->can('manage-dashboard-support-ticket')) {

                if ($user->type == 'client') {
                    return $this->clientDashboard();
                } elseif ($user->type == 'vendor') {
                    return $this->vendorDashboard();
                } elseif ($user->type == 'staff') {
                    return $this->staffDashboard();
                }

                $stats = $this->getStats(creatorId());
                $monthlyData = $this->getMonthlyData(creatorId());
                $recentTickets = $this->getRecentTickets(creatorId());
                $statusData = $this->getStatusData(creatorId());

                return $this->successResponse([
                    'stats' => $stats,
                    'monthlyData' => $monthlyData,
                    'recentTickets' => $recentTickets,
                    'statusData' => $statusData,
                ], 'Dashboard data retrieved successfully');
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    public function clientDashboard()
    {
        $user = Auth::user();
        if (!$user->can('manage-dashboard-support-ticket')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        return response()->json([
            'stats' => $this->getUserTicketStats($user->id),
            'monthlyData' => $this->getMonthlyDataByUser($user->id, 'user_id'),
            'recentTickets' => $this->getRecentTicketsByUser($user->id, 'user_id'),
            'statusData' => $this->getStatusDataByUser($user->id, 'user_id'),
        ]);
    }

    public function vendorDashboard()
    {
        $user = Auth::user();
        if (!$user->can('manage-dashboard-support-ticket')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        return response()->json([
            'stats' => $this->getUserTicketStats($user->id),
            'monthlyData' => $this->getMonthlyDataByUser($user->id, 'user_id'),
            'recentTickets' => $this->getRecentTicketsByUser($user->id, 'user_id'),
            'statusData' => $this->getStatusDataByUser($user->id, 'user_id'),
        ]);
    }

    public function staffDashboard()
    {
        $user = Auth::user();
        if (!$user->can('manage-dashboard-support-ticket')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        return response()->json([
            'stats' => $this->getStaffStats($user->id),
            'monthlyData' => $this->getStaffMonthlyData($user->id),
            'recentTickets' => $this->getStaffRecentTickets($user->id),
            'statusData' => $this->getStaffStatusData($user->id),
        ]);
    }

    private function getStats($createdBy)
    {
        $ticketStats = Ticket::where('created_by', $createdBy)
            ->selectRaw('COUNT(*) as total_tickets, SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) as open_tickets, SUM(CASE WHEN status = "Closed" THEN 1 ELSE 0 END) as closed_tickets, SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_tickets, AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_response_time')
            ->first();

        $otherStats = DB::table('ticket_categories')
            ->selectRaw('(SELECT COUNT(*) FROM ticket_categories WHERE created_by = ?) as categories, (SELECT COUNT(*) FROM support_ticket_knowledge_bases WHERE created_by = ?) as knowledge_base, (SELECT COUNT(*) FROM support_ticket_faqs WHERE created_by = ?) as faqs', [$createdBy, $createdBy, $createdBy])
            ->first();

        return [
            'totalTickets' => $ticketStats->total_tickets,
            'categories' => $otherStats->categories,
            'openTickets' => $ticketStats->open_tickets,
            'closedTickets' => $ticketStats->closed_tickets,
            'todayTickets' => $ticketStats->today_tickets,
            'avgResponseTime' => round($ticketStats->avg_response_time ?? 0, 1),
            'resolutionRate' => $ticketStats->total_tickets > 0 ? round(($ticketStats->closed_tickets / $ticketStats->total_tickets) * 100, 1) : 0,
        ];
    }

    private function getUserTicketStats($userId)
    {
        $stats = Ticket::where('user_id', $userId)
            ->selectRaw('COUNT(*) as total_tickets, SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) as open_tickets, SUM(CASE WHEN status = "Closed" THEN 1 ELSE 0 END) as closed_tickets, SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_tickets')
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
                        $subQ->where('creator_id', Auth::id())->orWhere('user_id', Auth::id());
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

    private function getMonthlyData($createdBy)
    {
        $monthlyData = Ticket::select([DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as total')])
            ->where('created_at', '>', DB::raw('DATE_SUB(NOW(),INTERVAL 1 YEAR)'))
            ->where('created_by', $createdBy)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month')->toArray();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $result = [];
        foreach ($months as $index => $month) {
            $result[$month] = $monthlyData[$index + 1] ?? 0;
        }
        return $result;
    }

    private function getMonthlyDataByUser($userId, $field)
    {
        $barChart = Ticket::select([DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as total')])
            ->where('created_at', '>', DB::raw('DATE_SUB(NOW(),INTERVAL 1 YEAR)'))
            ->where($field, $userId)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = [];
        foreach ($months as $index => $month) {
            $monthlyData[$month] = 0;
            foreach ($barChart as $chart) {
                if (intval($chart->month) == ($index + 1)) {
                    $monthlyData[$month] = $chart->total;
                }
            }
        }
        return $monthlyData;
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

    private function getStaffMonthlyDataByUser($userId)
    {
        $barChart = Ticket::select([DB::raw('MONTH(created_at) as month'), DB::raw('count(*) as total')])
            ->where('created_at', '>', DB::raw('DATE_SUB(NOW(),INTERVAL 1 YEAR)'))
            ->where(function ($q) use ($userId) {
                $q->where('creator_id', $userId)->orWhere('user_id', $userId);
            })
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = [];
        foreach ($months as $index => $month) {
            $monthlyData[$month] = 0;
            foreach ($barChart as $chart) {
                if (intval($chart->month) == ($index + 1)) {
                    $monthlyData[$month] = $chart->total;
                }
            }
        }
        return $monthlyData;
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

    private function getStaffRecentTicketsByUser($userId)
    {
        return Ticket::where(function ($q) use ($userId) {
            $q->where('creator_id', $userId)->orWhere('user_id', $userId);
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
                    'subject' => $ticket->status,
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
            ->get();

        $colors = ['In Progress' => '#F59E0B', 'On Hold' => '#EF4444', 'Closed' => '#10b77f'];

        return $statusData->map(function ($item) use ($colors) {
            return [
                'name' => $item->status,
                'value' => $item->count,
                'color' => $colors[$item->status] ?? '#6B7280'
            ];
        })->toArray();
    }

    private function getStatusDataByUser($userId, $field)
    {
        $statusData = Ticket::select('status', DB::raw('count(*) as count'))
            ->where($field, $userId)
            ->groupBy('status')
            ->get();

        $colors = ['In Progress' => '#F59E0B', 'On Hold' => '#EF4444', 'Closed' => '#10b77f'];

        return $statusData->map(function ($item) use ($colors) {
            return [
                'name' => $item->status,
                'value' => $item->count,
                'color' => $colors[$item->status] ?? '#6B7280'
            ];
        })->toArray();
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

    private function getStaffStatusDataByUser($userId)
    {
        $statusData = Ticket::select('status', DB::raw('count(*) as count'))
            ->where(function ($q) use ($userId) {
                $q->where('creator_id', $userId)->orWhere('user_id', $userId);
            })
            ->groupBy('status')
            ->get();

        $colors = ['In Progress' => '#F59E0B', 'On Hold' => '#EF4444', 'Closed' => '#10b77f'];

        return $statusData->map(function ($item) use ($colors) {
            return [
                'name' => $item->status,
                'value' => $item->count,
                'color' => $colors[$item->status] ?? '#6B7280'
            ];
        })->toArray();
    }
}
