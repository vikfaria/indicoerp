<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\JobType;
use Illuminate\Database\Seeder;



class DemoJobTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (JobType::where('created_by', $userId)->exists()) {
            return;
        }

        $types = [
            [
                'name' => 'Full Time',
                'description' => 'Permanent employment with full benefits and standard working hours',
                'is_active' => true
            ],
            [
                'name' => 'Part Time',
                'description' => 'Employment with reduced hours, typically less than 30 hours per week',
                'is_active' => true
            ],
            [
                'name' => 'Contract',
                'description' => 'Fixed-term employment for specific projects or duration',
                'is_active' => true
            ],
            [
                'name' => 'Freelance',
                'description' => 'Independent contractor work on project basis',
                'is_active' => false
            ],
            [
                'name' => 'Remote',
                'description' => 'Work from home or any location outside the office',
                'is_active' => true
            ],
            [
                'name' => 'Hybrid',
                'description' => 'Combination of remote and office-based work',
                'is_active' => true
            ],
            [
                'name' => 'Internship',
                'description' => 'Temporary position for students or recent graduates to gain experience',
                'is_active' => true
            ],
            [
                'name' => 'Temporary',
                'description' => 'Short-term employment to cover specific needs or peak periods',
                'is_active' => false
            ]
        ];

        foreach ($types as $type) {
            JobType::create([
                'name' => $type['name'],
                'description' => $type['description'],
                'is_active' => $type['is_active'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
