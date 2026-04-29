<?php

namespace Workdo\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Workdo\Lead\Models\Pipeline;
use Workdo\Lead\Models\LeadStage;
use Workdo\Lead\Models\DealStage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LeadUtility extends Model
{
    public static function defaultdata($company_id = null)
    {
        $pipelines = [
            'Sales',
        ];

        $lead_stages = [
            "Draft",
            "Sent",
            "Open",
            "Revised",
            "Declined",
            "Accepted",
        ];
        
        $dealstage = [
            'Initial Contact',
            'Qualification',
            'Meeting',
            'Proposal',
            'Close',
        ];

        if (!empty($company_id)) {
            foreach ($pipelines as $pipeline_name) {
                $pipeline = Pipeline::where('name', $pipeline_name)
                    ->where('created_by', $company_id)
                    ->first();
                
                if (empty($pipeline)) {
                    $pipeline = new Pipeline();
                    $pipeline->name = $pipeline_name;
                    $pipeline->creator_id = $company_id;                    
                    $pipeline->created_by = $company_id;
                    $pipeline->save();
                }

                // Create Lead Stages
                foreach ($lead_stages as $index => $stage_name) {
                    $leadStage = LeadStage::where('name', $stage_name)
                        ->where('created_by', $company_id)
                        ->first();
                    
                    if (empty($leadStage)) {
                        $leadStage = new LeadStage();
                        $leadStage->name = $stage_name;
                        $leadStage->pipeline_id = $pipeline->id;
                        $leadStage->order = $index + 1;
                        $leadStage->creator_id = $company_id;
                        $leadStage->created_by = $company_id;
                        $leadStage->save();
                    }
                }

                // Create Deal Stages
                foreach ($dealstage as $index => $stage_name) {
                    $dealStage = DealStage::where('name', $stage_name)
                        ->where('created_by', $company_id)
                        ->first();
                    
                    if (empty($dealStage)) {
                        $dealStage = new DealStage();
                        $dealStage->name = $stage_name;
                        $dealStage->pipeline_id = $pipeline->id;
                        $dealStage->order = $index + 1;
                        $dealStage->creator_id = $company_id;
                        $dealStage->created_by = $company_id;
                        $dealStage->save();
                    }
                }
            }
        }
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-crm-dashboard',
            'manage-own-lead',
            'create-lead',
        ];

        $client_permission = [
            'manage-crm-dashboard',
            'manage-own-lead',
            'view-leads',
            'manage-deals',
            'manage-own-deals',
            'view-deals',
            'create-deal-tasks',
            'edit-deal-tasks',
            'delete-deal-tasks',
            'manage-deal-tasks',
            'view-reports',
        ];
        
        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            foreach ($staff_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission)) {
                    if (!$roles_v->hasPermissionTo($permission_v)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            foreach ($client_permission as $permission_v) {
                $permission = Permission::where('name', $permission_v)->first();
                if (!empty($permission)) {
                    if (!$roles_v->hasPermissionTo($permission_v)) {
                        $roles_v->givePermissionTo($permission);
                    }
                }
            }
        }
    }
}