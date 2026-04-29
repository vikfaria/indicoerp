<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Deal;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\DealCall;
use Workdo\Lead\Models\LeadCall;
use Workdo\Lead\Models\DealEmail;
use Workdo\Lead\Models\LeadEmail;
use Workdo\Lead\Models\DealTask;
use Workdo\Lead\Models\LeadTask;
use Workdo\Lead\Models\DealActivityLog;
use Workdo\Lead\Models\LeadActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class DemoCallActivitySeeder extends Seeder
{
    public function run($userId): void
    {
        $this->seedLeadActivities($userId);
        $this->seedDealActivities($userId);
    }

    private function seedLeadActivities($userId): void
    {
        $leads = Lead::where('created_by', $userId)->get();
        
        if ($leads->isEmpty()) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $activities = ['call', 'email', 'task', 'file', 'product', 'move'];

        foreach ($leads as $lead) {
            
            // Randomly select 2 activities
            $selectedActivities = Arr::random($activities, 2);
            
            foreach ($selectedActivities as $activity) {
                switch ($activity) {
                    case 'call':
                        $this->createLeadCallActivity($lead, $user);
                        break;
                    case 'email':
                        $this->createLeadEmailActivity($lead, $user);
                        break;
                    case 'task':
                        $this->createLeadTaskActivity($lead, $user);
                        break;
                    case 'file':
                        $this->createLeadFileActivity($lead, $user);
                        break;
                    case 'product':
                        $this->createLeadProductActivity($lead, $user);
                        break;
                    case 'move':
                        $this->createLeadMoveActivity($lead, $user);
                        break;
                }
            }
        }
    }

    private function seedDealActivities($userId): void
    {
        $deals = Deal::where('created_by', $userId)->get();
        
        if ($deals->isEmpty()) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $activities = ['call', 'email', 'task', 'file', 'product', 'move'];

        foreach ($deals as $deal) {
            
            // Randomly select 2 activities
            $selectedActivities = Arr::random($activities, 2);
            
            foreach ($selectedActivities as $activity) {
                switch ($activity) {
                    case 'call':
                        $this->createDealCallActivity($deal, $user);
                        break;
                    case 'email':
                        $this->createDealEmailActivity($deal, $user);
                        break;
                    case 'task':
                        $this->createDealTaskActivity($deal, $user);
                        break;
                    case 'file':
                        $this->createDealFileActivity($deal, $user);
                        break;
                    case 'product':
                        $this->createDealProductActivity($deal, $user);
                        break;
                    case 'move':
                        $this->createDealMoveActivity($deal, $user);
                        break;
                }
            }
        }
    }

    private function createLeadCallActivity($lead, $user)
    {
        $calls = LeadCall::where('lead_id', $lead->id)->get();
        if ($calls->isEmpty()) {
            return;
        }
        foreach ($calls as $call) {
            LeadActivityLog::create([
                'lead_id' => $lead->id,
                'log_type' => 'Create Lead Call',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => $call->subject])
            ]);
        }
    }

    private function createLeadEmailActivity($lead, $user)
    {
        $emails = LeadEmail::where('lead_id', $lead->id)->get();
        if ($emails->isEmpty()) {
            return;
        }
        foreach ($emails as $email) {
            LeadActivityLog::create([
                'lead_id' => $lead->id,
                'log_type' => 'Create Lead Email',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => $email->subject])
            ]);
        }
    }

    private function createLeadTaskActivity($lead, $user)
    {
        $tasks = LeadTask::where('lead_id', $lead->id)->get();
        if ($tasks->isEmpty()) {
            return;
        }
        foreach ($tasks as $task) {
            LeadActivityLog::create([
                'lead_id' => $lead->id,
                'log_type' => 'Create Task',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => $task->name])
            ]);
        }
    }

    private function createLeadFileActivity($lead, $user)
    {
        if (!$lead || !$user) {
            return;
        }
        LeadActivityLog::create([
            'lead_id' => $lead->id,
            'log_type' => 'Upload File',
            'user_id' => $user->id,
            'remark' => json_encode(['file_name' => 'lead_file_' . $lead->id . '.pdf'])
        ]);
    }

    private function createLeadProductActivity($lead, $user)
    {
        if (!empty($lead->products)) {
            LeadActivityLog::create([
                'lead_id' => $lead->id,
                'log_type' => 'Add Product',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => 'Product added to ' . $lead->name])
            ]);
        }
    }

    private function createLeadMoveActivity($lead, $user)
    {
        $pipelineName = $lead->pipeline?->name ?? 'Sales';
        $leadStages = [
            'Sales' => ['Draft', 'Sent', 'Open', 'Revised', 'Declined', 'Accepted'],
            'Marketing' => ['Prospect', 'Contacted', 'Engaged', 'Qualified', 'Converted'],
            'Lead Qualification' => ['Unqualified', 'In Review', 'Qualified', 'Approved', 'Rejected']
        ];
        
        $stages = $leadStages[$pipelineName] ?? $leadStages['Sales'];
        $currentStage = $lead->stage?->name ?? $stages[0];
        $previousStage = $stages[array_rand($stages)];
        
        LeadActivityLog::create([
            'lead_id' => $lead->id,
            'log_type' => 'Move',
            'user_id' => $user->id,
            'remark' => json_encode([
                'title' => $lead->name,
                'old_status' => $previousStage,
                'new_status' => $currentStage
            ])
        ]);
    }

    private function createDealCallActivity($deal, $user)
    {
        $calls = DealCall::where('deal_id', $deal->id)->get();
        if ($calls->isEmpty()) {
            return;
        }
        foreach ($calls as $call) {
            DealActivityLog::create([
                'deal_id' => $deal->id,
                'log_type' => 'Create Deal Call',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => $call->subject])
            ]);
        }
    }

    private function createDealEmailActivity($deal, $user)
    {
        $emails = DealEmail::where('deal_id', $deal->id)->get();
        if ($emails->isEmpty()) {
            return;
        }
        foreach ($emails as $email) {
            DealActivityLog::create([
                'deal_id' => $deal->id,
                'log_type' => 'Create Deal Email',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => $email->subject])
            ]);
        }
    }

    private function createDealTaskActivity($deal, $user)
    {
        $tasks = DealTask::where('deal_id', $deal->id)->get();
        if ($tasks->isEmpty()) {
            return;
        }
        foreach ($tasks as $task) {
            DealActivityLog::create([
                'deal_id' => $deal->id,
                'log_type' => 'Create Task',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => $task->name])
            ]);
        }
    }

    private function createDealFileActivity($deal, $user)
    {
        if (!$deal || !$user) {
            return;
        }
        DealActivityLog::create([
            'deal_id' => $deal->id,
            'log_type' => 'Upload File',
            'user_id' => $user->id,
            'remark' => json_encode(['file_name' => 'deal_file_' . $deal->id . '.pdf'])
        ]);
    }

    private function createDealProductActivity($deal, $user)
    {
        if (!empty($deal->products)) {
            DealActivityLog::create([
                'deal_id' => $deal->id,
                'log_type' => 'Add Product',
                'user_id' => $user->id,
                'remark' => json_encode(['title' => 'Product added to ' . $deal->name])
            ]);
        }
    }

    private function createDealMoveActivity($deal, $user)
    {
        $pipelineName = $deal->pipeline?->name ?? 'Sales';
        $dealStages = [
            'Sales' => ['Initial Contact', 'Qualification', 'Meeting', 'Proposal', 'Close'],
            'Marketing' => ['Campaign Launch', 'Lead Generation', 'Nurturing', 'Qualification', 'Handoff'],
            'Lead Qualification' => ['Initial Contact', 'Needs Assessment', 'Solution Fit', 'Proposal Sent', 'Decision']
        ];
               
        
        $stages = $dealStages[$pipelineName] ?? $dealStages['Sales'];
        $currentStage = $deal->stage?->name ?? $stages[0];
        $previousStage = $stages[array_rand($stages)];
        
        DealActivityLog::create([
            'deal_id' => $deal->id,
            'log_type' => 'Move',
            'user_id' => $user->id,
            'remark' => json_encode([
                'title' => $deal->name,
                'old_status' => $previousStage,
                'new_status' => $currentStage
            ])
        ]);
    }
}