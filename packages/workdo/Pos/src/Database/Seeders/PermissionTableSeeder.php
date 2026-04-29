<?php

namespace Workdo\Pos\Database\Seeders;

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
            ['name' => 'manage-pos-dashboard', 'module' => 'pos', 'label' => 'Manage Pos Dashboard'],

            ['name' => 'manage-pos', 'module' => 'pos', 'label' => 'Manage Pos'],
            ['name' => 'create-pos', 'module' => 'pos', 'label' => 'Create Pos'],

            ['name' => 'manage-pos-orders', 'module' => 'pos-orders', 'label' => 'Manage Pos Orders'],
            ['name' => 'view-pos-orders', 'module' => 'pos-orders', 'label' => 'View Pos Orders'],

            ['name' => 'manage-pos-barcodes', 'module' => 'pos-barcodes', 'label' => 'Manage Pos Barcodes'],
            ['name' => 'print-pos-barcodes', 'module' => 'pos-barcodes', 'label' => 'Print Pos Barcodes'],

            ['name' => 'manage-pos-reports', 'module' => 'pos-reports', 'label' => 'Manage Pos Reports'],
            ['name' => 'view-pos-reports', 'module' => 'pos-reports', 'label' => 'View Pos Reports'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Pos',
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