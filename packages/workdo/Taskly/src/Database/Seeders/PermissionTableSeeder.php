<?php

namespace Workdo\Taskly\Database\Seeders;

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
            ['name' => 'manage-project-dashboard', 'module' => 'project', 'label' => 'Manage Project Dashboard'],

            ['name' => 'manage-project', 'module' => 'project', 'label' => 'Manage Project'],
            ['name' => 'manage-any-project', 'module' => 'project', 'label' => 'Manage All Project'],
            ['name' => 'manage-own-project', 'module' => 'project', 'label' => 'Manage Own Project'],
            ['name' => 'view-project', 'module' => 'project', 'label' => 'View Project'],
            ['name' => 'create-project', 'module' => 'project', 'label' => 'Create Project'],
            ['name' => 'edit-project', 'module' => 'project', 'label' => 'Edit Project'],
            ['name' => 'delete-project', 'module' => 'project', 'label' => 'Delete Project'],
            ['name' => 'duplicate-project', 'module' => 'project', 'label' => 'Duplicate Project'],

            ['name' => 'manage-project-report', 'module' => 'project-report', 'label' => 'Manage Project Report'],
            ['name' => 'view-project-report', 'module' => 'project-report', 'label' => 'View Project Report'],

            ['name' => 'invite-project-member', 'module' => 'project', 'label' => 'Invite Project Member'],
            ['name' => 'delete-project-member', 'module' => 'project', 'label' => 'Delete Project Member'],

            ['name' => 'invite-project-client', 'module' => 'project', 'label' => 'Invite Project Client'],
            ['name' => 'delete-project-client', 'module' => 'project', 'label' => 'Delete Project Client'],

            ['name' => 'manage-project-milestone', 'module' => 'project-milestone', 'label' => 'Manage Project Milestone'],
            ['name' => 'create-project-milestone', 'module' => 'project-milestone', 'label' => 'Create Project Milestone'],
            ['name' => 'edit-project-milestone', 'module' => 'project-milestone', 'label' => 'Edit Project Milestone'],
            ['name' => 'delete-project-milestone', 'module' => 'project-milestone', 'label' => 'Delete Project Milestone'],

            ['name' => 'manage-project-task', 'module' => 'project-task', 'label' => 'Manage Project Task'],
            ['name' => 'manage-any-project-task', 'module' => 'project-task', 'label' => 'Manage All Project Task'],
            ['name' => 'manage-own-project-task', 'module' => 'project-task', 'label' => 'Manage Own Project Task'],
            ['name' => 'create-project-task', 'module' => 'project-task', 'label' => 'Create Project Task'],
            ['name' => 'view-project-task', 'module' => 'project-task', 'label' => 'View Project Task'],
            ['name' => 'edit-project-task', 'module' => 'project-task', 'label' => 'Edit Project Task'],
            ['name' => 'delete-project-task', 'module' => 'project-task', 'label' => 'Delete Project Task'],

            ['name' => 'manage-project-task-comments', 'module' => 'project-task', 'label' => 'Manage Project Task Comments'],
            ['name' => 'create-project-task-comments', 'module' => 'project-task', 'label' => 'Create Project Task Comments'],
            ['name' => 'delete-project-task-comments', 'module' => 'project-task', 'label' => 'Delete Project Task Comments'],

            ['name' => 'manage-project-subtask', 'module' => 'project-task', 'label' => 'Manage Project Subtask'],
            ['name' => 'create-project-subtask', 'module' => 'project-task', 'label' => 'Create Project Subtask'],

            ['name' => 'manage-project-bug', 'module' => 'project-bug', 'label' => 'Manage Project Bug'],
            ['name' => 'manage-any-project-bug', 'module' => 'project-bug', 'label' => 'Manage All Project Bug'],
            ['name' => 'manage-own-project-bug', 'module' => 'project-bug', 'label' => 'Manage Own Project Bug'],
            ['name' => 'create-project-bug', 'module' => 'project-bug', 'label' => 'Create Project Bug'],
            ['name' => 'edit-project-bug', 'module' => 'project-bug', 'label' => 'Edit Project Bug'],
            ['name' => 'view-project-bug', 'module' => 'project-bug', 'label' => 'View Project Bug'],
            ['name' => 'delete-project-bug', 'module' => 'project-bug', 'label' => 'Delete Project Bug'],

            ['name' => 'manage-project-bug-comments', 'module' => 'project-bug', 'label' => 'Manage Project Bug Comments'],
            ['name' => 'create-project-bug-comments', 'module' => 'project-bug', 'label' => 'Create Project Bug Comments'],
            ['name' => 'delete-project-bug-comments', 'module' => 'project-bug', 'label' => 'Delete Project Bug Comments'],

            ['name' => 'manage-task-stages', 'module' => 'task-stages', 'label' => 'Manage Task Stages'],
            ['name' => 'manage-any-task-stages', 'module' => 'task-stages', 'label' => 'Manage All Task Stages'],
            ['name' => 'manage-own-task-stages', 'module' => 'task-stages', 'label' => 'Manage Own Task Stages'],
            ['name' => 'create-task-stages', 'module' => 'task-stages', 'label' => 'Create Task Stages'],
            ['name' => 'edit-task-stages', 'module' => 'task-stages', 'label' => 'Edit Task Stages'],
            ['name' => 'delete-task-stages', 'module' => 'task-stages', 'label' => 'Delete Task Stages'],

            ['name' => 'manage-bug-stages', 'module' => 'bug-stages', 'label' => 'Manage Bug Stages'],
            ['name' => 'manage-any-bug-stages', 'module' => 'bug-stages', 'label' => 'Manage All Bug Stages'],
            ['name' => 'manage-own-bug-stages', 'module' => 'bug-stages', 'label' => 'Manage Own Bug Stages'],
            ['name' => 'create-bug-stages', 'module' => 'bug-stages', 'label' => 'Create Bug Stages'],
            ['name' => 'edit-bug-stages', 'module' => 'bug-stages', 'label' => 'Edit Bug Stages'],
            ['name' => 'delete-bug-stages', 'module' => 'bug-stages', 'label' => 'Delete Bug Stages'],

        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Taskly',
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
