<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\Payroll;
use Workdo\Hrm\Models\Employee;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoPayrollSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Payroll::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        // Validate userId
        if (!$userId || !is_numeric($userId)) {
            return;
        }

        // Get employee count for realistic payroll data
        $employeeCount = Employee::where('created_by', $userId)
            ->whereHas('user')
            ->count();

        if ($employeeCount == 0) {
            return;
        }

        $months = [7, 8, 9, 10]; // July, August, September, October
        $year = 2025;
        
        $monthNames = [
            7 => 'July',
            8 => 'August', 
            9 => 'September',
            10 => 'October'
        ];

        $statuses = ['0', '1', '2']; // 0 = Draft, 1 = Completed, 2 = Processing

        // Create payroll for each month
        foreach ($months as $monthIndex => $month) {
            $startDate = Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $payDate = $endDate->copy()->addDays(5); // Pay 5 days after month end

            // Dynamic payroll data
            $frequency = 'monthly'; // All payrolls are monthly
            $status = $statuses[$monthIndex % 3];
            $monthName = $monthNames[$month];
            
            $payrollData = [
                'title' => "{$monthName} {$year} Payroll",
                'payroll_frequency' => $frequency,
                'pay_period_start' => $startDate->format('Y-m-d'),
                'pay_period_end' => $endDate->format('Y-m-d'),
                'pay_date' => $payDate->format('Y-m-d'),
                'notes' => "Monthly payroll processing for {$monthName} {$year}",
                'status' => 'draft',
                'is_payroll_paid' => 'unpaid',
                'creator_id' => $userId,
                'created_by' => $userId,
            ];

            // Use updateOrCreate to prevent duplicates
            Payroll::updateOrCreate(
                [
                    'title' => "{$monthName} {$year} Payroll",
                    'created_by' => $userId
                ],
                $payrollData
            );
        }
    }
}