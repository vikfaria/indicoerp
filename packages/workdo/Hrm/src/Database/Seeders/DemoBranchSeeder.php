<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Branch;

class DemoBranchSeeder extends Seeder
{
    public function run($userId)
    {

        if (Branch::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        $branches = [
            'Main Office',
            'Downtown Branch',
            'North Branch',
            'South Branch',
            'East Branch',
            'West Branch',
            'Corporate Headquarters',
            'Regional Office',
            'Sales Office',
            'Customer Service Center'
        ];

        foreach ($branches as $branchName) {
            Branch::updateOrCreate(
                [
                    'branch_name' => $branchName,
                    'created_by' => $userId
                ],
                [
                    'creator_id' => $userId
                ]
            );
        }
    }
}