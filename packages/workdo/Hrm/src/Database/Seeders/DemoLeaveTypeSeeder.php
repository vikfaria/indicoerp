<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\LeaveType;
use Illuminate\Database\Seeder;



class DemoLeaveTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (LeaveType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }

        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'description' => 'Yearly vacation leave for employees to rest and recharge.',
                'max_days_per_year' => 21,
                'is_paid' => true,
                'color' => '#10b77f'
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Medical leave for illness or health-related issues.',
                'max_days_per_year' => 10,
                'is_paid' => true,
                'color' => '#EF4444'
            ],
            [
                'name' => 'Maternity Leave',
                'description' => 'Leave for new mothers after childbirth.',
                'max_days_per_year' => 90,
                'is_paid' => true,
                'color' => '#F59E0B'
            ],
            [
                'name' => 'Paternity Leave',
                'description' => 'Leave for new fathers after childbirth.',
                'max_days_per_year' => 15,
                'is_paid' => true,
                'color' => '#3B82F6'
            ],
            [
                'name' => 'Personal Leave',
                'description' => 'Unpaid leave for personal matters and emergencies.',
                'max_days_per_year' => 5,
                'is_paid' => false,
                'color' => '#8B5CF6'
            ],
            [
                'name' => 'Bereavement Leave',
                'description' => 'Leave for mourning the loss of a family member.',
                'max_days_per_year' => 7,
                'is_paid' => true,
                'color' => '#6B7280'
            ],
            [
                'name' => 'Study Leave',
                'description' => 'Leave for educational purposes and professional development.',
                'max_days_per_year' => 30,
                'is_paid' => false,
                'color' => '#06B6D4'
            ],
            [
                'name' => 'Emergency Leave',
                'description' => 'Immediate leave for urgent family or personal emergencies.',
                'max_days_per_year' => 3,
                'is_paid' => true,
                'color' => '#DC2626'
            ]
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::updateOrCreate(
                [
                    'name' => $leaveType['name'],
                    'created_by' => $userId
                ],
                [
                    'description' => $leaveType['description'],
                    'max_days_per_year' => $leaveType['max_days_per_year'],
                    'is_paid' => $leaveType['is_paid'],
                    'color' => $leaveType['color'],
                    'creator_id' => $userId,
                ]
            );
        }
    }
}