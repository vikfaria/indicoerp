<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Overtime;
use Workdo\Hrm\Models\Employee;
use App\Models\User;

class DemoOvertimeSeeder extends Seeder
{
    public function run($userId)
    {
        if (Overtime::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        // Get employees (users who exist in employees table)
        $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
            ->where('created_by', $userId)
            ->pluck('id')
            ->toArray();

        // Check if we have required data
        if (empty($employees)) {
            return;
        }

        $overtimeTemplates = [
            [
                'title' => 'Weekend Project Work',
                'total_days' => 2,
                'hours' => 16.00,
                'rate' => 25.00,
                'notes' => 'Additional work on weekend for project completion and client deliverables.',
                'status' => 'active'
            ],
            [
                'title' => 'Holiday Emergency Support',
                'total_days' => 1,
                'hours' => 8.00,
                'rate' => 30.00,
                'notes' => 'Emergency support during holiday period for critical system maintenance.',
                'status' => 'active'
            ],
            [
                'title' => 'System Maintenance',
                'total_days' => 3,
                'hours' => 24.00,
                'rate' => 20.00,
                'notes' => 'After hours system maintenance and updates for server infrastructure.',
                'status' => 'active'
            ],
        ];

        foreach ($employees as $employeeId) {
            $template = $overtimeTemplates[array_rand($overtimeTemplates)];
            $startDate = now()->subDays(rand(5, 30));
            $endDate = $startDate->copy()->addDays($template['total_days'] - 1);

            $uniqueKey = [
                'employee_id' => $employeeId,
                'title' => $template['title'],
                'created_by' => $userId
            ];

            Overtime::updateOrCreate($uniqueKey, [
                'employee_id' => $employeeId,
                'title' => $template['title'],
                'total_days' => $template['total_days'],
                'hours' => $template['hours'],
                'rate' => $template['rate'],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'notes' => $template['notes'],
                'status' => $template['status'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
