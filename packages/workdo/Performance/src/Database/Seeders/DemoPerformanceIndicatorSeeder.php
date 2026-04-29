<?php

namespace Workdo\Performance\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Workdo\Performance\Models\PerformanceIndicator;
use Workdo\Performance\Models\PerformanceIndicatorCategory;

class DemoPerformanceIndicatorSeeder extends Seeder
{
    public function run($userId)
    {
        if (PerformanceIndicator::where('created_by', $userId)->exists()) {
            return;
        }

        $categories = PerformanceIndicatorCategory::all();
        
        if ($categories->isEmpty()) {
            return;
        }

        $indicators = [
            ['category' => 'Work Quality & Accuracy', 'name' => 'Error Rate', 'description' => 'Percentage of work containing errors', 'measurement_unit' => 'Percentage', 'target_value' => '< 2%', 'status' => 'active'],
            ['category' => 'Work Quality & Accuracy', 'name' => 'Work Accuracy', 'description' => 'Accuracy level in completed tasks', 'measurement_unit' => 'Percentage', 'target_value' => '> 98%', 'status' => 'inactive'],
            ['category' => 'Productivity & Efficiency', 'name' => 'Tasks Completed', 'description' => 'Number of tasks completed per week', 'measurement_unit' => 'Count', 'target_value' => '25', 'status' => 'active'],
            ['category' => 'Productivity & Efficiency', 'name' => 'Output Volume', 'description' => 'Total units produced per month', 'measurement_unit' => 'Units', 'target_value' => '500', 'status' => 'active'],
            ['category' => 'Team Collaboration', 'name' => 'Team Contribution Score', 'description' => 'Peer rating of team contributions', 'measurement_unit' => 'Rating (1-10)', 'target_value' => '8', 'status' => 'active'],
            ['category' => 'Team Collaboration', 'name' => 'Cross-functional Projects', 'description' => 'Number of collaborative projects participated', 'measurement_unit' => 'Count', 'target_value' => '3', 'status' => 'inactive'],
            ['category' => 'Problem Solving & Critical Thinking', 'name' => 'Issues Resolved', 'description' => 'Number of problems successfully resolved', 'measurement_unit' => 'Count', 'target_value' => '15', 'status' => 'active'],
            ['category' => 'Problem Solving & Critical Thinking', 'name' => 'Resolution Time', 'description' => 'Average time to resolve issues', 'measurement_unit' => 'Days', 'target_value' => '< 3', 'status' => 'active'],
            ['category' => 'Leadership & Mentoring', 'name' => 'Team Performance', 'description' => 'Overall performance rating of managed team', 'measurement_unit' => 'Rating (1-5)', 'target_value' => '4.2', 'status' => 'active'],
            ['category' => 'Leadership & Mentoring', 'name' => 'Mentorship Hours', 'description' => 'Hours spent mentoring team members', 'measurement_unit' => 'Hours', 'target_value' => '10', 'status' => 'inactive'],
            ['category' => 'Time Management & Organization', 'name' => 'Deadline Adherence', 'description' => 'Percentage of deadlines met', 'measurement_unit' => 'Percentage', 'target_value' => '95%', 'status' => 'active'],
            ['category' => 'Time Management & Organization', 'name' => 'Project Delivery', 'description' => 'On-time project completion rate', 'measurement_unit' => 'Percentage', 'target_value' => '90%', 'status' => 'active'],
            ['category' => 'Technical Competency', 'name' => 'Certification Completion', 'description' => 'Number of technical certifications obtained', 'measurement_unit' => 'Count', 'target_value' => '2', 'status' => 'inactive'],
            ['category' => 'Technical Competency', 'name' => 'Code Quality Score', 'description' => 'Code review rating average', 'measurement_unit' => 'Rating (1-10)', 'target_value' => '8.5', 'status' => 'active'],
            ['category' => 'Customer Focus & Service', 'name' => 'Customer Satisfaction', 'description' => 'Average customer satisfaction rating', 'measurement_unit' => 'Rating (1-5)', 'target_value' => '4.5', 'status' => 'active'],
            ['category' => 'Customer Focus & Service', 'name' => 'Response Rate', 'description' => 'Percentage of customer inquiries responded to', 'measurement_unit' => 'Percentage', 'target_value' => '100%', 'status' => 'active'],
            ['category' => 'Reliability & Accountability', 'name' => 'Attendance Rate', 'description' => 'Percentage of scheduled days present', 'measurement_unit' => 'Percentage', 'target_value' => '98%', 'status' => 'active'],
            ['category' => 'Reliability & Accountability', 'name' => 'Task Completion Rate', 'description' => 'Percentage of assigned tasks completed on time', 'measurement_unit' => 'Percentage', 'target_value' => '95%', 'status' => 'inactive'],
            ['category' => 'Professional Growth & Development', 'name' => 'Training Hours', 'description' => 'Hours spent in professional development', 'measurement_unit' => 'Hours', 'target_value' => '40', 'status' => 'active'],
            ['category' => 'Professional Growth & Development', 'name' => 'Skill Certifications', 'description' => 'Number of new skills or certifications acquired', 'measurement_unit' => 'Count', 'target_value' => '3', 'status' => 'inactive'],
        ];

        foreach ($indicators as $indicatorData) {
            $category = $categories->where('status', 'active')->firstWhere('name', $indicatorData['category']);
            
            if ($category) {
                PerformanceIndicator::updateOrCreate(
                    [
                        'name' => $indicatorData['name'],
                        'category_id' => $category->id,
                    ],
                    [
                        'description' => $indicatorData['description'],
                        'measurement_unit' => $indicatorData['measurement_unit'],
                        'target_value' => $indicatorData['target_value'],
                        'status' => $indicatorData['status'],
                        'creator_id' => $userId,
                        'created_by' => $userId,
                        'created_at' => Carbon::now()->subDays(rand(0, 180)),
                    ]
                );
            }
        }
    }
}
