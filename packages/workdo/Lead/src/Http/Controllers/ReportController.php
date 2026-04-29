<?php

namespace Workdo\Lead\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\Source;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Lead\Models\ClientDeal;
use Workdo\Lead\Models\DealStage;

class ReportController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('view-reports')){
            return Inertia::render('Lead/Reports/Index');
        }
        return back()->with('error', __('Permission denied'));
    }

    public function leadReports(Request $request)
    {
        if(Auth::user()->can('view-reports')){
            // Weekly Conversions (Pie Chart)
            $weeklyConversions = $this->getWeeklyConversions();
            
            // Sources Conversion (Bar Chart)
            $sourcesConversion = $this->getSourcesConversion();
            
            // Monthly Leads (Bar Chart)
            $monthlyLeads = $this->getMonthlyLeads();
            
            // Staff Leads (Bar Chart with date filter)
            $staffLeads = $this->getStaffLeads($request->from_date, $request->to_date);
            
            // Pipeline Leads (Bar Chart)
            $pipelineLeads = $this->getPipelineLeads();

            return Inertia::render('Lead/Reports/LeadReports', [
                'weeklyConversions' => $weeklyConversions,
                'sourcesConversion' => $sourcesConversion,
                'monthlyLeads' => $monthlyLeads,
                'staffLeads' => $staffLeads,
                'pipelineLeads' => $pipelineLeads,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    private function getWeeklyConversions()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $data = [];
        $isDemo = config('app.is_demo');
        
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dayName = $currentDay->format('l');
            
            $count = Lead::where('created_by', creatorId())
                ->whereDate('created_at', $currentDay)
                ->count();
            
            if ($isDemo && $count == 0) {
                // Professional volatile trend for weekly conversions
                $weeklyTrend = [4, 6, 3, 8, 5, 2, 4];
                $count = $weeklyTrend[$i] + rand(-1, 1);
            }

            if ($count > 0) {
                $data[] = [
                    'name' => $dayName,
                    'value' => $count
                ];
            }
        }
        
        if (empty($data) && !$isDemo) {
            $data = [
                ['name' => 'Monday', 'value' => 0],
                ['name' => 'Tuesday', 'value' => 1],
                ['name' => 'Wednesday', 'value' => 0],
                ['name' => 'Thursday', 'value' => 0],
                ['name' => 'Friday', 'value' => 0],
                ['name' => 'Saturday', 'value' => 0],
                ['name' => 'Sunday', 'value' => 0]
            ];
        }
        
        return $data;
    }

    private function getSourcesConversion()
    {
        $sources = Source::where('created_by', creatorId())->get();
        $data = [];
        $isDemo = config('app.is_demo');

        if ($isDemo && $sources->isEmpty()) {
            return [
                ['name' => 'Facebook', 'value' => rand(15, 25)],
                ['name' => 'Google', 'value' => rand(20, 35)],
                ['name' => 'LinkedIn', 'value' => rand(10, 20)],
                ['name' => 'Direct', 'value' => rand(5, 15)],
            ];
        }

        foreach ($sources as $source) {
            $count = Lead::where('created_by', creatorId())
                ->where('sources', 'like', '%' . $source->id . '%')
                ->count();
            
            if ($isDemo && $count < 5) {
                $count = rand(8, 20);
            }

            $data[] = [
                'name' => $source->name,
                'value' => $count
            ];
        }

        return $data;
    }

    private function getMonthlyLeads()
    {
        $data = [];
        $isDemo = config('app.is_demo');
        // Volatile professional growth trend for leads
        $leadTrend = [45, 38, 52, 44, 60, 55, 72, 68, 85, 78, 95, 88];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create(date('Y'), $month)->format('M Y');
            $count = Lead::where('created_by', creatorId())
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', date('Y'))
                ->count();
            
            if ($isDemo && $count < 10) {
                $count = $leadTrend[$month-1] + rand(-5, 5);
            }

            $data[] = [
                'month' => $month,
                'name' => $monthName,
                'leads' => $count
            ];
        }

        return $data;
    }

    private function getStaffLeads($fromDate = null, $toDate = null)
    {
        $lead_user = User::where('created_by', '=', creatorId())->emp([],['vendor'])->get();
        $isDemo = config('app.is_demo');
        
        $data = [];
        
        if ($isDemo && $lead_user->isEmpty()) {
            return [
                ['name' => 'Staff A', 'leads' => rand(15, 25)],
                ['name' => 'Staff B', 'leads' => rand(12, 22)],
                ['name' => 'Staff C', 'leads' => rand(18, 30)],
                ['name' => 'Staff D', 'leads' => rand(10, 18)],
            ];
        }

        foreach ($lead_user as $lead_user_data) {
            if (!empty($fromDate) && !empty($toDate)) {
                $form_date = date('Y-m-d', strtotime($fromDate));
                $to_date = date('Y-m-d', strtotime($toDate));
                
                $lead_count = Lead::select('leads.*')
                    ->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')
                    ->where('user_leads.user_id', '=', $lead_user_data->id)
                    ->where('leads.date', '>=', $form_date)
                    ->where('leads.date', '<=', $to_date)
                    ->count();
            } else {
                $lead_count = Lead::select('leads.*')
                    ->join('user_leads', 'user_leads.lead_id', '=', 'leads.id')
                    ->where('user_leads.user_id', '=', $lead_user_data->id)
                    ->count();
            }
            
            if ($isDemo && $lead_count < 5) {
                $lead_count = rand(10, 25);
            }

            $data[] = [
                'name' => $lead_user_data->name,
                'leads' => $lead_count
            ];
        }
        
        return $data;
    }

    private function getPipelineLeads()
    {
        $pipelines = Pipeline::where('created_by', creatorId())->get();
        $data = [];
        $isDemo = config('app.is_demo');

        if ($isDemo && $pipelines->isEmpty()) {
            return [
                ['name' => 'Sales Pipeline', 'leads' => rand(25, 45)],
                ['name' => 'Marketing Pipeline', 'leads' => rand(15, 30)],
                ['name' => 'Service Pipeline', 'leads' => rand(10, 20)],
            ];
        }

        foreach ($pipelines as $pipeline) {
            $count = Lead::where('created_by', creatorId())
                ->where('pipeline_id', $pipeline->id)
                ->count();
            
            if ($isDemo && $count < 5) {
                $count = rand(15, 40);
            }

            $data[] = [
                'name' => $pipeline->name,
                'leads' => $count
            ];
        }

        return $data;
    }

    public function dealReports(Request $request)
    {
        if(Auth::user()->can('view-reports')){
            // Weekly Deal Conversions (Pie Chart)
            $weeklyDealConversions = $this->getWeeklyDealConversions();
            
            // Deal Sources Conversion (Bar Chart)
            $dealSourcesConversion = $this->getDealSourcesConversion();
            
            // Monthly Deals (Bar Chart)
            $monthlyDeals = $this->getMonthlyDeals();
            
            // Staff Deals (Bar Chart with date filter)
            $staffDeals = $this->getStaffDeals($request->from_date, $request->to_date);
            
            // Client Deals (Bar Chart with date filter)
            $clientDeals = $this->getClientDeals($request->from_date, $request->to_date);
            
            // Pipeline Deals (Bar Chart)
            $pipelineDeals = $this->getPipelineDeals();
            
            // Deals by Stage Chart (with pipeline filter)
            $pipelineId = $request->get('pipeline_id');
            $dealStageChart = [];
            $dealStages = DealStage::where('created_by', creatorId())
                ->when($pipelineId, fn($q) => $q->where('pipeline_id', $pipelineId))
                ->orderBy('order', 'ASC')
                ->get();
            
            $isDemo = config('app.is_demo');

            foreach ($dealStages as $index => $stage) {
                $dealCount = Deal::where('created_by', creatorId())
                    ->where('stage_id', $stage->id)
                    ->count();
                
                if ($isDemo && $dealCount < 5) {
                    $stageTrend = [15, 12, 10, 8, 5, 3];
                    $dealCount = ($stageTrend[$index % count($stageTrend)]) + rand(-2, 2);
                    if ($dealCount < 0) $dealCount = rand(1, 3);
                }

                $dealStageChart[] = [
                    'name' => $stage->name,
                    'deals' => $dealCount
                ];
            }
            
            // Get all pipelines for dropdown
            $pipelines = Pipeline::where('created_by', creatorId())->get(['id', 'name']);
            if ($isDemo && $pipelines->isEmpty()) {
                $pipelines = collect([
                    ['id' => 1, 'name' => 'Sales Pipeline'],
                    ['id' => 2, 'name' => 'Marketing Pipeline'],
                ]);
            }

            return Inertia::render('Lead/Reports/DealReports', [
                'weeklyDealConversions' => $weeklyDealConversions,
                'dealSourcesConversion' => $dealSourcesConversion,
                'monthlyDeals' => $monthlyDeals,
                'staffDeals' => $staffDeals,
                'clientDeals' => $clientDeals,
                'pipelineDeals' => $pipelineDeals,
                'dealStageChart' => $dealStageChart,
                'pipelines' => $pipelines,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    private function getWeeklyDealConversions()
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $data = [];
        $isDemo = config('app.is_demo');
        
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);
            $dayName = $currentDay->format('l');
            
            $count = Deal::where('created_by', creatorId())
                ->whereDate('created_at', $currentDay)
                ->count();
            
            if ($isDemo && $count == 0) {
                // Volatile professional weekly trend for deals
                $weeklyTrend = [3, 5, 2, 7, 4, 1, 3];
                $count = $weeklyTrend[$i] + rand(-1, 1);
            }

            if ($count > 0) {
                $data[] = [
                    'name' => $dayName,
                    'value' => $count
                ];
            }
        }
        
        if (empty($data) && !$isDemo) {
            $data = [
                ['name' => 'Monday', 'value' => 0],
                ['name' => 'Tuesday', 'value' => 1],
                ['name' => 'Wednesday', 'value' => 0],
                ['name' => 'Thursday', 'value' => 0],
                ['name' => 'Friday', 'value' => 0],
                ['name' => 'Saturday', 'value' => 0],
                ['name' => 'Sunday', 'value' => 0]
            ];
        }
        
        return $data;
    }

    private function getDealSourcesConversion()
    {
        $sources = Source::where('created_by', creatorId())->get();
        $data = [];
        $isDemo = config('app.is_demo');

        if ($isDemo && $sources->isEmpty()) {
            return [
                ['name' => 'Inbound Calls', 'value' => rand(10, 18)],
                ['name' => 'Email Marketing', 'value' => rand(15, 25)],
                ['name' => 'Direct Sales', 'value' => rand(8, 15)],
                ['name' => 'Partner Referral', 'value' => rand(5, 12)],
            ];
        }

        foreach ($sources as $source) {
            $count = Deal::where('created_by', creatorId())
                ->where('sources', 'like', '%' . $source->id . '%')
                ->count();
            
            if ($isDemo && $count < 3) {
                $count = rand(5, 15);
            }

            $data[] = [
                'name' => $source->name,
                'value' => $count
            ];
        }

        return $data;
    }

    private function getMonthlyDeals()
    {
        $data = [];
        $isDemo = config('app.is_demo');
        // Volatile professional growth trend for deals
        $dealTrend = [35, 28, 42, 36, 48, 44, 55, 50, 65, 60, 75, 70];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::create(date('Y'), $month)->format('M Y');
            $count = Deal::where('created_by', creatorId())
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', date('Y'))
                ->count();
            
            if ($isDemo && $count < 8) {
                $count = $dealTrend[$month-1] + rand(-5, 5);
            }

            $data[] = [
                'month' => $month,
                'name' => $monthName,
                'deals' => $count
            ];
        }

        return $data;
    }

    private function getStaffDeals($fromDate = null, $toDate = null)
    {
        $deal_user = User::where('created_by', '=', creatorId())->emp()->get();
        $isDemo = config('app.is_demo');
        
        $data = [];
        
        if ($isDemo && $deal_user->isEmpty()) {
            return [
                ['name' => 'Agent Alpha', 'deals' => rand(10, 20)],
                ['name' => 'Agent Bravo', 'deals' => rand(8, 15)],
                ['name' => 'Agent Charlie', 'deals' => rand(12, 22)],
                ['name' => 'Agent Delta', 'deals' => rand(5, 12)],
            ];
        }

        foreach ($deal_user as $deal_user_data) {
            if (!empty($fromDate) && !empty($toDate)) {
                $form_date = date('Y-m-d', strtotime($fromDate));
                $to_date = date('Y-m-d', strtotime($toDate));
                
                $deal_count = Deal::select('deals.*')
                    ->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')
                    ->where('user_deals.user_id', '=', $deal_user_data->id)
                    ->whereDate('deals.created_at', '>=', $form_date)
                    ->whereDate('deals.created_at', '<=', $to_date)
                    ->count();
            } else {
                $deal_count = Deal::select('deals.*')
                    ->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')
                    ->where('user_deals.user_id', '=', $deal_user_data->id)
                    ->count();
            }
            
            if ($isDemo && $deal_count < 3) {
                $deal_count = rand(8, 18);
            }

            $data[] = [
                'name' => $deal_user_data->name,
                'deals' => $deal_count
            ];
        }
        
        return $data;
    }

    private function getPipelineDeals()
    {
        $pipelines = Pipeline::where('created_by', creatorId())->get();
        $data = [];
        $isDemo = config('app.is_demo');

        if ($isDemo && $pipelines->isEmpty()) {
            return [
                ['name' => 'Direct Sales', 'deals' => rand(20, 40)],
                ['name' => 'Channel Sales', 'deals' => rand(15, 25)],
                ['name' => 'Partner Sales', 'deals' => rand(10, 18)],
            ];
        }

        foreach ($pipelines as $pipeline) {
            $count = Deal::where('created_by', creatorId())
                ->where('pipeline_id', $pipeline->id)
                ->count();
            
            if ($isDemo && $count < 3) {
                $count = rand(10, 30);
            }

            $data[] = [
                'name' => $pipeline->name,
                'deals' => $count
            ];
        }

        return $data;
    }

    private function getClientDeals($fromDate = null, $toDate = null)
    {
        $client_deal = User::where('created_by', '=', creatorId())->where('type', '=', 'client')->get();
        $isDemo = config('app.is_demo');
        
        $data = [];
        
        if ($isDemo && $client_deal->isEmpty()) {
            return [
                ['name' => 'Client X', 'deals' => rand(5, 12)],
                ['name' => 'Client Y', 'deals' => rand(3, 8)],
                ['name' => 'Client Z', 'deals' => rand(4, 10)],
            ];
        }

        foreach ($client_deal as $client_deal_data) {
            if (!empty($fromDate) && !empty($toDate)) {
                $form_date = date('Y-m-d', strtotime($fromDate));
                $to_date = date('Y-m-d', strtotime($toDate));
                
                $deals_client = ClientDeal::where('client_id', $client_deal_data->id)
                    ->whereDate('created_at', '>=', $form_date)
                    ->whereDate('created_at', '<=', $to_date)
                    ->count();
            } else {
                $deals_client = ClientDeal::where('client_id', $client_deal_data->id)->count();
            }
            
            if ($isDemo && $deals_client < 2) {
                $deals_client = rand(5, 12);
            }

            $data[] = [
                'name' => $client_deal_data->name,
                'deals' => $deals_client
            ];
        }
        
        return $data;
    }
}