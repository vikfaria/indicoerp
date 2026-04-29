<?php

namespace Workdo\LandingPage\Database\Seeders;

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
            ['name' => 'manage-landing-page', 'module' => 'landing-page', 'label' => 'Manage LandingPage'],
            ['name' => 'view-landing-page', 'module' => 'landing-page', 'label' => 'View LandingPage'],
            ['name' => 'edit-landing-page', 'module' => 'landing-page', 'label' => 'Edit LandingPage'],
            // ['name' => 'manage-marketplace-settings', 'module' => 'landing-page', 'label' => 'Manage Marketplace Settings'],
            ['name' => 'manage-custom-pages', 'module' => 'landing-page', 'label' => 'Manage Custom Pages'],
            ['name' => 'create-custom-pages', 'module' => 'landing-page', 'label' => 'Create Custom Pages'],
            ['name' => 'edit-custom-pages', 'module' => 'landing-page', 'label' => 'Edit Custom Pages'],
            ['name' => 'delete-custom-pages', 'module' => 'landing-page', 'label' => 'Delete Custom Pages'],
            ['name' => 'view-custom-pages', 'module' => 'landing-page', 'label' => 'View Custom Pages'],
            ['name' => 'manage-newsletter-subscribers', 'module' => 'landing-page', 'label' => 'Manage Newsletter Subscribers'],
            ['name' => 'view-newsletter-subscribers', 'module' => 'landing-page', 'label' => 'View Newsletter Subscribers'],
            ['name' => 'edit-newsletter-subscribers', 'module' => 'landing-page', 'label' => 'Edit Newsletter Subscribers'],
            ['name' => 'delete-newsletter-subscribers', 'module' => 'landing-page', 'label' => 'Delete Newsletter Subscribers'],
            ['name' => 'export-newsletter-subscribers', 'module' => 'landing-page', 'label' => 'Export Newsletter Subscribers'],
        ];

        $company_role = Role::where('name', 'superadmin')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'LandingPage',
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