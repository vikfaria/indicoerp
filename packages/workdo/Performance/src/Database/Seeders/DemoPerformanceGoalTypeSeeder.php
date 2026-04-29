<?php

namespace Workdo\Performance\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Performance\Models\PerformanceGoalType;

class DemoPerformanceGoalTypeSeeder extends Seeder
{
    public function run($userId)
    {
        if (PerformanceGoalType::where('created_by', $userId)->exists()) {
            return;
        }

        $goalTypes = [
            ['name' => 'Performance Excellence', 'description' => 'Achieve outstanding work quality and maximize productivity', 'status' => 'active'],
            ['name' => 'Skill Development', 'description' => 'Acquire new competencies and enhance existing skills', 'status' => 'active'],
            ['name' => 'Team Collaboration', 'description' => 'Improve teamwork and cross-functional cooperation', 'status' => 'inactive'],
            ['name' => 'Project Delivery', 'description' => 'Successfully complete projects within scope and timeline', 'status' => 'active'],
            ['name' => 'Revenue Growth', 'description' => 'Increase sales performance and revenue generation', 'status' => 'active'],
            ['name' => 'Customer Excellence', 'description' => 'Enhance customer satisfaction and service quality', 'status' => 'active'],
            ['name' => 'Quality Assurance', 'description' => 'Maintain high standards and reduce defects', 'status' => 'inactive'],
            ['name' => 'Process Improvement', 'description' => 'Optimize workflows and operational efficiency', 'status' => 'active'],
            ['name' => 'Innovation & Creativity', 'description' => 'Generate new ideas and creative problem-solving', 'status' => 'active'],
            ['name' => 'Leadership Growth', 'description' => 'Develop leadership skills and management capabilities', 'status' => 'active'],
            ['name' => 'Cost Optimization', 'description' => 'Reduce expenses and improve resource utilization', 'status' => 'inactive'],
            ['name' => 'Market Development', 'description' => 'Expand market presence and customer base', 'status' => 'active'],
            ['name' => 'Compliance & Standards', 'description' => 'Ensure adherence to regulations and company policies', 'status' => 'active'],
            ['name' => 'Strategic Objectives', 'description' => 'Align with organizational strategy and vision', 'status' => 'active'],
            ['name' => 'Operational Efficiency', 'description' => 'Streamline operations and improve productivity', 'status' => 'inactive'],
        ];

        foreach ($goalTypes as $goalType) {
            PerformanceGoalType::create([
                'name' => $goalType['name'],
                'description' => $goalType['description'],
                'status' => $goalType['status'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
