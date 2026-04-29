<?php

namespace Workdo\Lead\Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [
            ['name' => 'manage-crm-dashboard', 'module' => 'lead', 'label' => 'Manage CRM Dashboard'],            

            // Pipeline management
            ['name' => 'manage-pipelines', 'module' => 'pipelines', 'label' => 'Manage Pipelines'],
            ['name' => 'manage-any-pipelines', 'module' => 'pipelines', 'label' => 'Manage All Pipelines'],
            ['name' => 'manage-own-pipelines', 'module' => 'pipelines', 'label' => 'Manage Own Pipelines'],
            ['name' => 'create-pipelines', 'module' => 'pipelines', 'label' => 'Create Pipelines'],
            ['name' => 'edit-pipelines', 'module' => 'pipelines', 'label' => 'Edit Pipelines'],
            ['name' => 'delete-pipelines', 'module' => 'pipelines', 'label' => 'Delete Pipelines'],

            // LeadStage management
            ['name' => 'manage-lead-stages', 'module' => 'lead-stages', 'label' => 'Manage LeadStages'],
            ['name' => 'manage-any-lead-stages', 'module' => 'lead-stages', 'label' => 'Manage All LeadStages'],
            ['name' => 'manage-own-lead-stages', 'module' => 'lead-stages', 'label' => 'Manage Own LeadStages'],
            ['name' => 'create-lead-stages', 'module' => 'lead-stages', 'label' => 'Create LeadStages'],
            ['name' => 'edit-lead-stages', 'module' => 'lead-stages', 'label' => 'Edit LeadStages'],
            ['name' => 'delete-lead-stages', 'module' => 'lead-stages', 'label' => 'Delete LeadStages'],

            // DealStage management
            ['name' => 'manage-deal-stages', 'module' => 'deal-stages', 'label' => 'Manage DealStages'],
            ['name' => 'manage-any-deal-stages', 'module' => 'deal-stages', 'label' => 'Manage All DealStages'],
            ['name' => 'manage-own-deal-stages', 'module' => 'deal-stages', 'label' => 'Manage Own DealStages'],
            ['name' => 'create-deal-stages', 'module' => 'deal-stages', 'label' => 'Create DealStages'],
            ['name' => 'edit-deal-stages', 'module' => 'deal-stages', 'label' => 'Edit DealStages'],
            ['name' => 'delete-deal-stages', 'module' => 'deal-stages', 'label' => 'Delete DealStages'],

            // Label management
            ['name' => 'manage-labels', 'module' => 'labels', 'label' => 'Manage Labels'],
            ['name' => 'manage-any-labels', 'module' => 'labels', 'label' => 'Manage All Labels'],
            ['name' => 'manage-own-labels', 'module' => 'labels', 'label' => 'Manage Own Labels'],
            ['name' => 'create-labels', 'module' => 'labels', 'label' => 'Create Labels'],
            ['name' => 'edit-labels', 'module' => 'labels', 'label' => 'Edit Labels'],
            ['name' => 'delete-labels', 'module' => 'labels', 'label' => 'Delete Labels'],

            // Source management
            ['name' => 'manage-sources', 'module' => 'sources', 'label' => 'Manage Sources'],
            ['name' => 'manage-any-sources', 'module' => 'sources', 'label' => 'Manage All Sources'],
            ['name' => 'manage-own-sources', 'module' => 'sources', 'label' => 'Manage Own Sources'],
            ['name' => 'create-sources', 'module' => 'sources', 'label' => 'Create Sources'],
            ['name' => 'edit-sources', 'module' => 'sources', 'label' => 'Edit Sources'],
            ['name' => 'delete-sources', 'module' => 'sources', 'label' => 'Delete Sources'],

            // Lead management
            ['name' => 'manage-leads', 'module' => 'leads', 'label' => 'Manage Leads'],
            ['name' => 'manage-any-leads', 'module' => 'leads', 'label' => 'Manage All Leads'],
            ['name' => 'manage-own-leads', 'module' => 'leads', 'label' => 'Manage Own Leads'],
            ['name' => 'view-leads', 'module' => 'leads', 'label' => 'View Leads'],
            ['name' => 'create-leads', 'module' => 'leads', 'label' => 'Create Leads'],
            ['name' => 'edit-leads', 'module' => 'leads', 'label' => 'Edit Leads'],
            ['name' => 'delete-leads', 'module' => 'leads', 'label' => 'Delete Leads'],
            ['name' => 'lead-move', 'module' => 'leads', 'label' => 'Move Leads'],

            // Lead Task management
            ['name' => 'manage-lead-tasks', 'module' => 'lead-tasks', 'label' => 'Manage Lead Tasks'],
            ['name' => 'manage-any-lead-tasks', 'module' => 'lead-tasks', 'label' => 'Manage All Lead Tasks'],
            ['name' => 'manage-own-lead-tasks', 'module' => 'lead-tasks', 'label' => 'Manage Own Lead Tasks'],
            ['name' => 'create-lead-tasks', 'module' => 'lead-tasks', 'label' => 'Create Lead Tasks'],
            ['name' => 'edit-lead-tasks', 'module' => 'lead-tasks', 'label' => 'Edit Lead Tasks'],
            ['name' => 'delete-lead-tasks', 'module' => 'lead-tasks', 'label' => 'Delete Lead Tasks'],

            // Deal management
            ['name' => 'manage-deals', 'module' => 'deals', 'label' => 'Manage Deals'],
            ['name' => 'manage-any-deals', 'module' => 'deals', 'label' => 'Manage All Deals'],
            ['name' => 'manage-own-deals', 'module' => 'deals', 'label' => 'Manage Own Deals'],
            ['name' => 'view-deals', 'module' => 'deals', 'label' => 'View Deals'],
            ['name' => 'create-deals', 'module' => 'deals', 'label' => 'Create Deals'],
            ['name' => 'edit-deals', 'module' => 'deals', 'label' => 'Edit Deals'],
            ['name' => 'delete-deals', 'module' => 'deals', 'label' => 'Delete Deals'],
            ['name' => 'deal-move', 'module' => 'deals', 'label' => 'Move Deals'],

            // Deal Task management
            ['name' => 'manage-deal-tasks', 'module' => 'deal-tasks', 'label' => 'Manage Deal Tasks'],
            ['name' => 'manage-any-deal-tasks', 'module' => 'deal-tasks', 'label' => 'Manage All Deal Tasks'],
            ['name' => 'manage-own-deal-tasks', 'module' => 'deal-tasks', 'label' => 'Manage Own Deal Tasks'],
            ['name' => 'create-deal-tasks', 'module' => 'deal-tasks', 'label' => 'Create Deal Tasks'],
            ['name' => 'edit-deal-tasks', 'module' => 'deal-tasks', 'label' => 'Edit Deal Tasks'],
            ['name' => 'delete-deal-tasks', 'module' => 'deal-tasks', 'label' => 'Delete Deal Tasks'],

            // Report management
            ['name' => 'manage-reports', 'module' => 'reports', 'label' => 'Manage Reports'],
            ['name' => 'view-reports', 'module' => 'reports', 'label' => 'View Reports'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Lead',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }
    }
}