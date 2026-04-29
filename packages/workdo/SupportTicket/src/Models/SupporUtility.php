<?php

namespace Workdo\SupportTicket\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Workdo\SupportTicket\Models\TicketCategory;

class SupporUtility extends Model
{
    public static function defaultdata($company_id = null)
    {
        $categories = [
            'Technical Support',
            'Billing',
            'General Inquiry',
            'Bug Report',
            'Feature Request',
        ];

        if (!empty($company_id)) {
            foreach ($categories as $index => $category_name) {
                $category = TicketCategory::where('name', $category_name)
                    ->where('created_by', $company_id)
                    ->first();

                if (empty($category)) {
                    $category = new TicketCategory();
                    $category->name = $category_name;
                    $category->color = self::getDefaultColor($index);
                    $category->created_by = !empty($company_id) ? $company_id : 2;
                    $category->save();
                }
            }
        }
    }

    private static function getDefaultColor($index)
    {
        $colors = ['#3B82F6', '#10b77f', '#F59E0B', '#8B5CF6', '#EF4444'];
        return $colors[$index % count($colors)];
    }

    public static function GivePermissionToRoles($role_id = null, $rolename = null)
    {
        $staff_permissions = [
            'manage-dashboard-support-ticket',
            'manage-support-tickets',
            'manage-own-support-tickets',
            'view-support-tickets',
            'create-support-tickets',
            'edit-support-tickets',
            'delete-support-tickets',
            'reply-support-tickets',
            'manage-own-ticket-categories',
            'manage-ticket-categories',
            'create-ticket-categories',
            'edit-ticket-categories',
            'delete-ticket-categories',
            'manage-own-knowledge-base',
            'manage-knowledge-base',
            'create-knowledge-base',
            'edit-knowledge-base',
            'delete-knowledge-base',
            'manage-any-knowledge-base',
            'manage-faq',
            'create-faq',
            'edit-faq',
            'delete-faq',
            'manage-contact',
            'view-contact',
        ];

        $client_permissions = [
            'manage-dashboard-support-ticket',
            'manage-own-support-tickets',
            'manage-support-tickets',
            'view-support-tickets',
            'reply-support-tickets',
            'manage-ticket-categories',
            'manage-own-ticket-categories',
            'manage-knowledge-base',
            'manage-own-knowledge-base',
            'manage-faq',
            'manage-ticket-categories',
            'manage-any-ticket-categories',
            'manage-knowledge-base',
            'manage-any-knowledge-base',
            'manage-faq',
            'manage-contact',
            'view-contact',
        ];

        $vendor_permissions = [
            'manage-dashboard-support-ticket',
            'manage-support-tickets',
            'manage-own-support-tickets',
            'view-support-tickets',
            'reply-support-tickets',
            'manage-ticket-categories',
            'manage-knowledge-base',
            'manage-own-knowledge-base',
            'manage-faq',
            'manage-ticket-categories',
            'manage-own-ticket-categories',
            'manage-knowledge-base',
            'manage-own-knowledge-base',
            'manage-faq',
            'manage-ticket-categories',
            'manage-any-ticket-categories',
            'manage-knowledge-base',
            'manage-any-knowledge-base',
            'manage-faq',
            'manage-contact',
            'view-contact',
        ];

        if ($rolename == 'staff') {
            $roles_v = Role::where('name', 'staff')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($staff_permissions as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }
        }

        if ($rolename == 'client') {
            $roles_v = Role::where('name', 'client')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($client_permissions as $permission_v) {
                    $permission = Permission::where('name', $permission_v)->first();
                    if (!empty($permission)) {
                        if (!$roles_v->hasPermissionTo($permission_v)) {
                            $roles_v->givePermissionTo($permission);
                        }
                    }
                }
            }
        }

        if ($rolename == 'vendor') {
            $roles_v = Role::where('name', 'vendor')->where('id', $role_id)->first();
            if ($roles_v) {
                foreach ($vendor_permissions as $permission_v) {
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