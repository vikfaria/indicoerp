<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\AwardType;
use Illuminate\Database\Seeder;



class DemoAwardTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (AwardType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        $awardTypes = [
            ['name' => 'Employee of the Month', 'description' => 'Awarded to the best performing employee each month.'],
            ['name' => 'Best Team Player', 'description' => 'Recognizes outstanding collaboration and teamwork.'],
            ['name' => 'Customer Hero Award', 'description' => 'For delivering exceptional customer service.'],
            ['name' => 'Innovation Champion', 'description' => 'Honors employees who introduce creative solutions.'],
            ['name' => 'Leadership Excellence', 'description' => 'Awarded for demonstrating strong leadership qualities.'],
            ['name' => 'Rookie of the Year', 'description' => 'For the most promising new employee of the year.'],
            ['name' => 'Outstanding Attendance', 'description' => 'Recognizes consistent punctuality and attendance.'],
            ['name' => 'Sales Star', 'description' => 'Awarded for achieving top sales performance.'],
            ['name' => 'Excellence in Quality', 'description' => 'For maintaining exceptional standards of work quality.'],
            ['name' => 'Community Contributor', 'description' => 'Recognizes employees who contribute to community initiatives.'],
            ['name' => 'Tech Innovator', 'description' => 'For creating or improving technology-based processes.'],
            ['name' => 'Mentor of the Year', 'description' => 'For providing exceptional guidance and mentorship.'],
            ['name' => 'Best Problem Solver', 'description' => 'Awarded to the employee with outstanding analytical skills.'],
            ['name' => 'Long Service Award', 'description' => 'Recognizes employees for long-term dedication and service.'],
            ['name' => 'Culture Champion', 'description' => 'For embodying and promoting company values consistently.'],
        ];

        foreach ($awardTypes as $type) {
            AwardType::updateOrCreate(
                ['name' => $type['name']], // search condition
                [
                    'description' => $type['description'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]
            );
        }
    }
}
