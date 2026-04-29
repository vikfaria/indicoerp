<?php

namespace Workdo\Lead\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\Lead\Models\ClientDeal;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\DealStage;
use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Models\LeadTask;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\UserLead;

class DashboardApiController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        try {
            if (Auth::user()->can('manage-crm-dashboard')) {
                $user     = Auth::user();
                $userType = $user->type;

                switch ($userType) {
                    case 'company': 
                        return $this->companyDashboard();
                    case 'staff': 
                         default: 
                        return $this->userDashboard();
                }
            } else {
                return $this->errorResponse('Permission denied');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }

    private function userDashboard()
    {
        $user            = Auth::user();
        $assignedLeadIds = UserLead::where('user_id', $user->id)->pluck('lead_id');

        $assignedLeads = Lead::whereIn('id', $assignedLeadIds)->count();

        $completedTasks = LeadTask::whereIn('lead_id', $assignedLeadIds)->where('status', 'Complete')->count();

        $pendingTasks = LeadTask::whereIn('lead_id', $assignedLeadIds)->where('status', 'On Going')->count();

        $recentLeads = Lead::whereIn('id', $assignedLeadIds)->orderBy('created_at', 'desc')->take(5)->get(['id', 'name', 'subject', 'created_at']);

        $taskStatusChart = [
            ['name' => 'Completed', 'value' => $completedTasks],
            ['name' => 'Pending', 'value' => $pendingTasks]
        ];

        $data = [
            'assigned_leads'  => $assignedLeads,
            'completed_tasks' => $completedTasks,
            'pending_tasks'   => $pendingTasks,
            'recentLeads'     => $recentLeads,
            'taskStatusChart' => $taskStatusChart,
        ];

        return $this->successResponse($data, 'User dashboard data retrieved successfully');
    }
    private function companyDashboard()
    {
        $users        = User::where('created_by', creatorId());
        $lead         = Lead::where('created_by', creatorId());
        $totalLeads   = $lead->count();
        $totalUsers   = $users->where('type', '!=', 'client')->count();
        $totalClients = $users->where('type', 'client')->count();

        $recentLeads = $lead->orderBy('created_at', 'desc')->limit(5)->get(['id', 'name', 'subject', 'created_at']);

        $data = [

            'total_leads'   => $totalLeads,
            'total_users'   => $totalUsers,
            'total_clients' => $totalClients,
            'recentLeads'   => $recentLeads,
        ];

        return $this->successResponse($data, 'Company dashboard data retrieved successfully');
    }

    public function chartData(Request $request)
    {
        try {
            $pipelineId     = $request->get('pipeline_id');
            $leadStageChart = [];
            $leadStages     = LeadStage::where('created_by', creatorId())
                ->when($pipelineId, fn($q) => $q->where('pipeline_id', $pipelineId))
                ->orderBy('order', 'ASC')
                ->get();

            foreach ($leadStages as $stage) {
                $leadCount = Lead::where('created_by', creatorId())
                    ->where('stage_id', $stage->id)
                    ->count();

                $leadStageChart[] = [
                    'name'  => $stage->name,
                    'leads' => $leadCount
                ];
            }

            return $this->successResponse($leadStageChart, 'Chart data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Something went wrong');
        }
    }
}
