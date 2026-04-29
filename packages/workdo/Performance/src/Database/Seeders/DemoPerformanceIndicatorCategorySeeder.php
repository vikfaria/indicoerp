<?php

namespace Workdo\Performance\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Performance\Models\PerformanceIndicatorCategory;

class DemoPerformanceIndicatorCategorySeeder extends Seeder
{
    public function run($userId)
    {
        if (PerformanceIndicatorCategory::where('created_by', $userId)->exists()) {
            return;
        }

        $categories = [
            ['name' => 'Work Quality & Accuracy', 'description' => 'Precision, attention to detail, and error-free delivery', 'status' => 'active'],
            ['name' => 'Productivity & Efficiency', 'description' => 'Output volume, task completion rate, and resource optimization', 'status' => 'active'],
            ['name' => 'Communication & Interpersonal Skills', 'description' => 'Clarity in communication and relationship building', 'status' => 'inactive'],
            ['name' => 'Team Collaboration', 'description' => 'Contribution to team success and cross-functional cooperation', 'status' => 'active'],
            ['name' => 'Problem Solving & Critical Thinking', 'description' => 'Analytical approach to challenges and solution development', 'status' => 'active'],
            ['name' => 'Leadership & Mentoring', 'description' => 'Ability to guide teams, develop others, and drive results', 'status' => 'active'],
            ['name' => 'Innovation & Process Improvement', 'description' => 'Creative thinking and contribution to operational enhancements', 'status' => 'inactive'],
            ['name' => 'Time Management & Organization', 'description' => 'Prioritization skills, deadline adherence, and workflow efficiency', 'status' => 'active'],
            ['name' => 'Technical Competency', 'description' => 'Job-specific skills, tool proficiency, and technical knowledge', 'status' => 'active'],
            ['name' => 'Customer Focus & Service', 'description' => 'Client satisfaction, responsiveness, and service quality delivery', 'status' => 'active'],
            ['name' => 'Adaptability & Learning Agility', 'description' => 'Flexibility to change and speed of acquiring new skills', 'status' => 'inactive'],
            ['name' => 'Initiative & Self-Direction', 'description' => 'Proactive behavior, self-motivation, and independent work capability', 'status' => 'inactive'],
            ['name' => 'Reliability & Accountability', 'description' => 'Consistency, dependability, and ownership of responsibilities', 'status' => 'active'],
            ['name' => 'Professional Growth & Development', 'description' => 'Commitment to skill enhancement and career advancement', 'status' => 'active'],
            ['name' => 'Decision Making & Judgment', 'description' => 'Quality of choices made and business impact of decisions', 'status' => 'inactive'],
        ];

        foreach ($categories as $category) {
            PerformanceIndicatorCategory::create([
                'name' => $category['name'],
                'description' => $category['description'],
                'status' => $category['status'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}