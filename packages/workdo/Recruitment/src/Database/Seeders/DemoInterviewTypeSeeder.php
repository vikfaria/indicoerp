<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\InterviewType;
use Illuminate\Database\Seeder;



class DemoInterviewTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (InterviewType::where('created_by', $userId)->exists()) {
            return;
        }

        $types = [
            [
                'name' => 'Phone Interview',
                'description' => 'Initial screening interview conducted over phone',
                'is_active' => true
            ],
            [
                'name' => 'Video Interview',
                'description' => 'Remote interview using video conferencing tools like Zoom or Teams',
                'is_active' => true
            ],
            [
                'name' => 'In-Person Interview',
                'description' => 'Face-to-face interview at company office or designated location',
                'is_active' => true
            ],
            [
                'name' => 'Panel Interview',
                'description' => 'Interview conducted by multiple interviewers simultaneously',
                'is_active' => false
            ],
            [
                'name' => 'Technical Interview',
                'description' => 'Assessment of technical skills and knowledge relevant to the role',
                'is_active' => true
            ],
            [
                'name' => 'Behavioral Interview',
                'description' => 'Focus on past behavior and situational responses',
                'is_active' => true
            ],
            [
                'name' => 'Group Interview',
                'description' => 'Multiple candidates interviewed together in group setting',
                'is_active' => false
            ],
            [
                'name' => 'Final Interview',
                'description' => 'Last round interview typically with senior management or decision makers',
                'is_active' => true
            ]
        ];

        foreach ($types as $type) {
            InterviewType::create([
                'name' => $type['name'],
                'description' => $type['description'],
                'is_active' => $type['is_active'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
