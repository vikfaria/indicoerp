<?php

namespace Workdo\ProductService\Database\Seeders;

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
            ['name' => 'manage-product-service-item', 'module' => 'product-service-item', 'label' => 'Manage Product Service'],
            ['name' => 'manage-any-product-service-item', 'module' => 'product-service-item', 'label' => 'Manage All Product Service'],
            ['name' => 'manage-own-product-service-item', 'module' => 'product-service-item', 'label' => 'Manage Own Product Service'],
            ['name' => 'view-product-service-item', 'module' => 'product-service-item', 'label' => 'View Product Service'],
            ['name' => 'create-product-service-item', 'module' => 'product-service-item', 'label' => 'Create Product Service'],
            ['name' => 'edit-product-service-item', 'module' => 'product-service-item', 'label' => 'Edit Product Service'],
            ['name' => 'delete-product-service-item', 'module' => 'product-service-item', 'label' => 'Delete Product Service'],
            
            ['name' => 'manage-stock', 'module' => 'product-service-item', 'label' => 'Manage Stock'],
            ['name' => 'create-stock', 'module' => 'product-service-item', 'label' => 'Create Stock'],

            ['name' => 'manage-product-service-categories', 'module' => 'product-service-category', 'label' => 'Manage Categories'],
            ['name' => 'manage-any-product-service-categories', 'module' => 'product-service-category', 'label' => 'Manage All Categories'],
            ['name' => 'manage-own-product-service-categories', 'module' => 'product-service-category', 'label' => 'Manage Own Categories'],
            ['name' => 'create-product-service-categories', 'module' => 'product-service-category', 'label' => 'Create Categories'],
            ['name' => 'edit-product-service-categories', 'module' => 'product-service-category', 'label' => 'Edit Categories'],
            ['name' => 'delete-product-service-categories', 'module' => 'product-service-category', 'label' => 'Delete Categories'],

            ['name' => 'manage-product-service-taxes', 'module' => 'product-service-tax', 'label' => 'Manage Taxes'],
            ['name' => 'manage-any-product-service-taxes', 'module' => 'product-service-tax', 'label' => 'Manage All Taxes'],
            ['name' => 'manage-own-product-service-taxes', 'module' => 'product-service-tax', 'label' => 'Manage Own Taxes'],
            ['name' => 'create-product-service-taxes', 'module' => 'product-service-tax', 'label' => 'Create Taxes'],
            ['name' => 'edit-product-service-taxes', 'module' => 'product-service-tax', 'label' => 'Edit Taxes'],
            ['name' => 'delete-product-service-taxes', 'module' => 'product-service-tax', 'label' => 'Delete Taxes'],

            ['name' => 'manage-product-service-units', 'module' => 'product-service-unit', 'label' => 'Manage Units'],
            ['name' => 'manage-any-product-service-units', 'module' => 'product-service-unit', 'label' => 'Manage All Units'],
            ['name' => 'manage-own-product-service-units', 'module' => 'product-service-unit', 'label' => 'Manage Own Units'],
            ['name' => 'create-product-service-units', 'module' => 'product-service-unit', 'label' => 'Create Units'],
            ['name' => 'edit-product-service-units', 'module' => 'product-service-unit', 'label' => 'Edit Units'],
            ['name' => 'delete-product-service-units', 'module' => 'product-service-unit', 'label' => 'Delete Units'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'ProductService',
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
