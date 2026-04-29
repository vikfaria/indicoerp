<?php

namespace Workdo\SupportTicket\Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Workdo\SupportTicket\Models\SupportTicketCustomPage;
use Workdo\SupportTicket\Models\QuickLink;
use Workdo\SupportTicket\Models\SupporUtility;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [
            // Dashboard
            ['name' => 'manage-dashboard-support-ticket', 'module' => 'dashboard', 'label' => 'Manage Support Ticket Dashboard'],
            
            // Support Ticket management
            ['name' => 'manage-support-tickets', 'module' => 'support-tickets', 'label' => 'Manage Support Tickets'],
            ['name' => 'manage-any-support-tickets', 'module' => 'support-tickets', 'label' => 'Manage All Support Tickets'],
            ['name' => 'manage-own-support-tickets', 'module' => 'support-tickets', 'label' => 'Manage Own Support Tickets'],
            ['name' => 'view-support-tickets', 'module' => 'support-tickets', 'label' => 'View Support Tickets'],
            ['name' => 'create-support-tickets', 'module' => 'support-tickets', 'label' => 'Create Support Tickets'],
            ['name' => 'edit-support-tickets', 'module' => 'support-tickets', 'label' => 'Edit Support Tickets'],
            ['name' => 'delete-support-tickets', 'module' => 'support-tickets', 'label' => 'Delete Support Tickets'],
            ['name' => 'reply-support-tickets', 'module' => 'support-tickets', 'label' => 'Reply Support Tickets'],

            // Ticket Categories management
            ['name' => 'manage-ticket-categories', 'module' => 'ticket-categories', 'label' => 'Manage Categories'],
            ['name' => 'manage-any-ticket-categories', 'module' => 'ticket-categories', 'label' => 'Manage All Categories'],
            ['name' => 'manage-own-ticket-categories', 'module' => 'ticket-categories', 'label' => 'Manage Own Categories'],
            ['name' => 'create-ticket-categories', 'module' => 'ticket-categories', 'label' => 'Create Categories'],
            ['name' => 'edit-ticket-categories', 'module' => 'ticket-categories', 'label' => 'Edit Categories'],
            ['name' => 'delete-ticket-categories', 'module' => 'ticket-categories', 'label' => 'Delete Categories'],

            // Knowledge Base management
            ['name' => 'manage-knowledge-base', 'module' => 'knowledge-base', 'label' => 'Manage Knowledge Base'],
            ['name' => 'manage-any-knowledge-base', 'module' => 'knowledge-base', 'label' => 'Manage All Knowledge Base'],
            ['name' => 'manage-own-knowledge-base', 'module' => 'knowledge-base', 'label' => 'Manage Own Knowledge Base'],
            ['name' => 'create-knowledge-base', 'module' => 'knowledge-base', 'label' => 'Create Knowledge Base'],
            ['name' => 'edit-knowledge-base', 'module' => 'knowledge-base', 'label' => 'Edit Knowledge Base'],
            ['name' => 'delete-knowledge-base', 'module' => 'knowledge-base', 'label' => 'Delete Knowledge Base'],

            // FAQ management
            ['name' => 'manage-faq', 'module' => 'faq', 'label' => 'Manage FAQ'],
            ['name' => 'manage-any-faq', 'module' => 'faq', 'label' => 'Manage All FAQ'],
            ['name' => 'manage-own-faq', 'module' => 'faq', 'label' => 'Manage Own FAQ'],
            ['name' => 'create-faq', 'module' => 'faq', 'label' => 'Create FAQ'],
            ['name' => 'edit-faq', 'module' => 'faq', 'label' => 'Edit FAQ'],
            ['name' => 'delete-faq', 'module' => 'faq', 'label' => 'Delete FAQ'],

            // Contact management
            ['name' => 'manage-contact', 'module' => 'contact', 'label' => 'Manage Contact'],
            ['name' => 'manage-any-contact', 'module' => 'contact', 'label' => 'Manage All Contact'],
            ['name' => 'manage-own-contact', 'module' => 'contact', 'label' => 'Manage Own Contact'],
            ['name' => 'view-contact', 'module' => 'contact', 'label' => 'View Contact'],
            ['name' => 'delete-contact', 'module' => 'contact', 'label' => 'Delete Contact'],

            // Support Settings
            ['name' => 'manage-support-settings', 'module' => 'support-settings', 'label' => 'Manage Support Settings'],
            ['name' => 'edit-support-settings', 'module' => 'support-settings', 'label' => 'Edit Support Settings'],
            ['name' => 'manage-support-ticket-settings', 'module' => 'support-ticket-settings', 'label' => 'Manage Support Ticket Settings'],

            // Title Sections
            ['name' => 'manage-support-ticket-title-sections', 'module' => 'support-ticket-title-sections', 'label' => 'Manage Title Sections'],
            ['name' => 'edit-support-ticket-title-sections', 'module' => 'support-ticket-title-sections', 'label' => 'Edit Title Sections'],

            // Contact Information
            ['name' => 'manage-support-ticket-contact-information', 'module' => 'support-ticket-contact-information', 'label' => 'Manage Contact Information'],
            ['name' => 'create-support-ticket-contact-information', 'module' => 'support-ticket-custom-pages', 'label' => 'Create Contact Information'],

            // CTA Sections
            ['name' => 'manage-support-ticket-cta-sections', 'module' => 'support-ticket-cta-sections', 'label' => 'Manage CTA Sections'],
            ['name' => 'create-support-ticket-cta-sections', 'module' => 'support-ticket-cta-sections', 'label' => 'Create CTA Sections'],

            // Custom Pages
            ['name' => 'manage-support-ticket-custom-pages', 'module' => 'support-ticket-custom-pages', 'label' => 'Manage Custom Pages'],
            ['name' => 'create-support-ticket-custom-pages', 'module' => 'support-ticket-custom-pages', 'label' => 'Create Custom Pages'],
            ['name' => 'edit-support-ticket-custom-pages', 'module' => 'support-ticket-custom-pages', 'label' => 'Edit Custom Pages'],
            ['name' => 'delete-support-ticket-custom-pages', 'module' => 'support-ticket-custom-pages', 'label' => 'Delete Custom Pages'],

            // Quick Links
            ['name' => 'manage-support-ticket-quick-links', 'module' => 'support-ticket-quick-links', 'label' => 'Manage Quick Links'],
            ['name' => 'create-support-ticket-quick-links', 'module' => 'support-ticket-quick-links', 'label' => 'Create Quick Links'],
            ['name' => 'edit-support-ticket-quick-links', 'module' => 'support-ticket-quick-links', 'label' => 'Edit Quick Links'],
            ['name' => 'delete-support-ticket-quick-links', 'module' => 'support-ticket-quick-links', 'label' => 'Delete Quick Links'],

            // Support Information
            ['name' => 'manage-support-ticket-support-information', 'module' => 'support-ticket-support-information', 'label' => 'Manage Support Information'],
            ['name' => 'edit-support-ticket-support-information', 'module' => 'support-ticket-quick-links', 'label' => 'Edit Support Information'],

            // Brand Settings
            ['name' => 'manage-support-ticket-brand-settings', 'module' => 'support-ticket-brand-settings', 'label' => 'Manage Brand Settings'],
            ['name' => 'edit-support-ticket-brand-settings', 'module' => 'support-ticket-custom-pages', 'label' => 'Edit Brand Settings'],
        ];

        $company_role = Role::where('name', 'company')->first();
        $staff_role = Role::where('name', 'staff')->first();
        $client_role = Role::where('name', 'client')->first();
        $vendor_role = Role::where('name', 'vendor')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'SupportTicket',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }

        // Assign permissions to staff, client, and vendor roles
        if ($staff_role) {
           SupporUtility::GivePermissionToRoles($staff_role->id, 'staff');
        }
        if ($client_role) {
           SupporUtility::GivePermissionToRoles($client_role->id, 'client');
        }
        if ($vendor_role) {
           SupporUtility::GivePermissionToRoles($vendor_role->id, 'vendor');
        }

        // Create default custom pages and quick links for existing companies
        $companies = User::where('type', 'company')->get();
        foreach ($companies as $company) {
           SupportTicketCustomPage::defaultdata($company->id);
           QuickLink::defaultdata($company->id);
        }
    }
}