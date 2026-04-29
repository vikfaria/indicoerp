<?php

namespace Workdo\Goal\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Goal\Models\GoalCategory;

class DemoCategorySeeder extends Seeder
{
    public function run($userId): void
    {
        if (GoalCategory::where('created_by', $userId)->exists()) {
            return;
        }

        $categories = [
            [
                'category_name' => 'Personal Development',
                'category_code' => 'PERSONAL_DEV',
                'description' => 'Goals focused on self-improvement and skill development',
                'is_active' => true,
            ],
            [
                'category_name' => 'Career Growth',
                'category_code' => 'CAREER',
                'description' => 'Professional advancement and career-related objectives',
                'is_active' => true,
            ],
            [
                'category_name' => 'Health & Fitness',
                'category_code' => 'HEALTH',
                'description' => 'Physical and mental wellness goals',
                'is_active' => true,
            ],
            [
                'category_name' => 'Financial',
                'category_code' => 'FINANCE',
                'description' => 'Money management and financial planning goals',
                'is_active' => true,
            ],
            [
                'category_name' => 'Education',
                'category_code' => 'EDUCATION',
                'description' => 'Learning and educational achievement goals',
                'is_active' => true,
            ],
            [
                'category_name' => 'Business',
                'category_code' => 'BUSINESS',
                'description' => 'Business development and entrepreneurial goals',
                'is_active' => true,
            ],
            [
                'category_name' => 'Relationships',
                'category_code' => 'RELATIONSHIPS',
                'description' => 'Social connections and relationship building goals',
                'is_active' => false,
            ],
            [
                'category_name' => 'Creative',
                'category_code' => 'CREATIVE',
                'description' => 'Artistic and creative expression goals',
                'is_active' => false,
            ],
            [
                'category_name' => 'Travel',
                'category_code' => 'TRAVEL',
                'description' => 'Travel and exploration objectives',
                'is_active' => true,
            ],
            [
                'category_name' => 'Technology',
                'category_code' => 'TECH',
                'description' => 'Technology learning and digital skills goals',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            GoalCategory::create(array_merge($category, [
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
