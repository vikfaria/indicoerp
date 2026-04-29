<?php

namespace Workdo\Performance\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PerformanceUtility extends Model
{
    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permission = [
            'manage-performance',
            'manage-performance-dashboard',

            'manage-performance-indicators',
            'manage-own-performance-indicators',
            'create-performance-indicators',
            'view-performance-indicators',
            'edit-performance-indicators',
            'delete-performance-indicators',

            'manage-performance-indicator-categories',
            'manage-any-performance-indicator-categories',
            'create-performance-indicator-categories',
            'view-performance-indicator-categories',
            'edit-performance-indicator-categories',
            'delete-performance-indicator-categories',

            'manage-employee-goals',
            'manage-own-employee-goals',
            'create-employee-goals',
            'view-employee-goals',
            'edit-employee-goals',
            'delete-employee-goals',

            'manage-goal-types',
            'manage-any-goal-types',
            'create-goal-types',
            'view-goal-types',
            'edit-goal-types',
            'delete-goal-types',

            'manage-review-cycles',
            'manage-own-review-cycles',
            'create-review-cycles',
            'view-review-cycles',
            'edit-review-cycles',
            'delete-review-cycles',

            'manage-employee-reviews',
            'manage-own-employee-reviews',
            'create-employee-reviews',
            'view-employee-reviews',
            'edit-employee-reviews',
            'delete-employee-reviews',
            'conduct-employee-reviews',
        ];

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($staff_permission as $permission_v) {
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
}