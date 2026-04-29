<?php

namespace Workdo\Quotation\Database\Seeders;

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
            ['name' => 'manage-quotations', 'module' => 'quotation', 'label' => 'Manage Quotation'],
            ['name' => 'manage-any-quotations', 'module' => 'quotation', 'label' => 'Manage All Quotation'],
            ['name' => 'manage-own-quotations', 'module' => 'quotation', 'label' => 'Manage Own Quotation'],
            ['name' => 'view-quotations', 'module' => 'quotation', 'label' => 'View Quotation'],
            ['name' => 'create-quotations', 'module' => 'quotation', 'label' => 'Create Quotation'],
            ['name' => 'edit-quotations', 'module' => 'quotation', 'label' => 'Edit Quotation'],
            ['name' => 'delete-quotations', 'module' => 'quotation', 'label' => 'Delete Quotation'],
            ['name' => 'print-quotations', 'module' => 'quotation', 'label' => 'Print Quotation'],
            ['name' => 'sent-quotations', 'module' => 'quotation', 'label' => 'Sent Quotation'],
            ['name' => 'approve-quotations', 'module' => 'quotation', 'label' => 'Approve Quotation'],
            ['name' => 'reject-quotations', 'module' => 'quotation', 'label' => 'Reject Quotation'],
            ['name' => 'convert-to-invoice-quotations', 'module' => 'quotation', 'label' => 'Convert to Invoice Quotation'],
            ['name' => 'create-quotations-revision', 'module' => 'quotation', 'label' => 'Create Quotation Revision'],
            ['name' => 'duplicate-quotations', 'module' => 'quotation', 'label' => 'Duplicate Quotation'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Quotation',
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