<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\ChecklistItem;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\OnboardingChecklist;

class DemoChecklistItemSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ChecklistItem::where('created_by', $userId)->exists()) {
            return;
        }

        $categories = ['Documentation', 'IT Setup', 'Training', 'HR', 'Facilities', 'Other'];
        $roles = ['HR Manager', 'IT Admin', 'Direct Manager', 'Facilities', 'Training Coordinator'];

        $checklists = OnboardingChecklist::where('created_by', $userId)->where('status', 1)->get();

        if ($checklists->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            ChecklistItem::create([
                'checklist_id' => $checklists->random()->id,
                'task_name' => fake()->sentence(3),
                'description' => fake()->sentence(10),
                'category' => fake()->randomElement($categories),
                'assigned_to_role' => fake()->randomElement($roles),
                'due_day' => fake()->numberBetween(1, 30),
                'is_required' => fake()->boolean(70),
                'status' => fake()->boolean(80),
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
