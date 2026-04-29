<?php

namespace Workdo\Performance\Database\Seeders;

use Carbon\Carbon;
use Workdo\Performance\Models\PerformanceReviewCycle;
use Illuminate\Database\Seeder;

class DemoPerformanceReviewCycleSeeder extends Seeder
{
    public function run($userId): void
    {
        if (PerformanceReviewCycle::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {
            $reviewCycles = [
                ['name' => '2024 Year-End Performance Appraisal', 'frequency' => 'annual', 'description' => 'Annual performance evaluation covering goal achievement and career development', 'status' => 'inactive'],
                ['name' => 'Q4 2024 Quarterly Business Review', 'frequency' => 'quarterly', 'description' => 'Fourth quarter assessment focusing on KPI achievement and deliverables', 'status' => 'inactive'],
                ['name' => 'Q1 2025 Performance Check-In', 'frequency' => 'quarterly', 'description' => 'First quarter review for goal setting and milestone tracking', 'status' => 'active'],
                ['name' => '2025 Mid-Year Performance Evaluation', 'frequency' => 'semi-annual', 'description' => 'Mid-year review covering annual objectives and competency development', 'status' => 'active'],
                ['name' => 'January 2025 Progress Assessment', 'frequency' => 'monthly', 'description' => 'Monthly performance check for goal progress and development needs', 'status' => 'inactive'],
                ['name' => 'February 2025 Development Review', 'frequency' => 'monthly', 'description' => 'Monthly evaluation focusing on skill enhancement and growth opportunities', 'status' => 'inactive'],
                ['name' => 'March 2025 Quarterly Prep Review', 'frequency' => 'monthly', 'description' => 'Monthly check for quarterly goal preparation and optimization', 'status' => 'active'],
                ['name' => 'New Hire Probationary Assessment', 'frequency' => 'quarterly', 'description' => 'Performance evaluation for probationary employees and cultural fit', 'status' => 'active'],
                ['name' => 'Leadership Excellence Program Review', 'frequency' => 'semi-annual', 'description' => 'Leadership assessment covering management effectiveness and team development', 'status' => 'active'],
                ['name' => 'Cross-Functional Project Performance Review', 'frequency' => 'quarterly', 'description' => 'Project-based evaluation assessing collaboration and deliverable quality', 'status' => 'active'],
                ['name' => 'Sales Team Performance Review', 'frequency' => 'quarterly', 'description' => 'Sales performance evaluation focusing on revenue targets and client relations', 'status' => 'active'],
                ['name' => 'Technical Skills Assessment Cycle', 'frequency' => 'semi-annual', 'description' => 'Technical competency evaluation covering skill proficiency and innovation', 'status' => 'active'],
                ['name' => 'Customer Service Excellence Review', 'frequency' => 'quarterly', 'description' => 'Customer service assessment including satisfaction scores and quality metrics', 'status' => 'active'],
                ['name' => 'Remote Work Performance Evaluation', 'frequency' => 'semi-annual', 'description' => 'Remote employee assessment focusing on productivity and communication', 'status' => 'active'],
                ['name' => 'Executive Leadership Assessment', 'frequency' => 'annual', 'description' => 'Executive performance review covering strategic vision and organizational impact', 'status' => 'active']
            ];

            foreach ($reviewCycles as $cycle) {
                PerformanceReviewCycle::create([
                    'name'       => $cycle['name'],
                    'frequency'  => $cycle['frequency'],
                    'description' => $cycle['description'],
                    'status'     => $cycle['status'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                    'created_at' => Carbon::now()->subDays(rand(0, 180)),
                ]);
            }
        }
    }
}
