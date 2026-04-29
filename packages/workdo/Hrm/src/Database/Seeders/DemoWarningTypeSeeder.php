<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\WarningType;
use Illuminate\Database\Seeder;



class DemoWarningTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (WarningType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $warningTypes = [
            ['warning_type_name' => 'Late Attendance'],
            ['warning_type_name' => 'Unprofessional Behavior'],
            ['warning_type_name' => 'Missed Deadline'],
            ['warning_type_name' => 'Violation of Company Policy'],
            ['warning_type_name' => 'Unauthorized Absence'],
            ['warning_type_name' => 'Improper Dress Code'],
            ['warning_type_name' => 'Negligence at Work'],
            ['warning_type_name' => 'Disrespect to Supervisor'],
            ['warning_type_name' => 'Use of Offensive Language'],
            ['warning_type_name' => 'Misuse of Company Property'],
            ['warning_type_name' => 'Repeated Errors'],
            ['warning_type_name' => 'Failure to Follow Instructions'],
            ['warning_type_name' => 'Poor Team Collaboration'],
            ['warning_type_name' => 'Insubordination'],
            ['warning_type_name' => 'Data Privacy Violation'],
            ['warning_type_name' => 'Delay in Task Submission'],
            ['warning_type_name' => 'Improper Communication with Client'],
            ['warning_type_name' => 'Unapproved Leave'],
            ['warning_type_name' => 'Workplace Misconduct'],
            ['warning_type_name' => 'Non-Compliance with Safety Rules'],
        ];

        foreach ($warningTypes as $type) {
            WarningType::updateOrCreate(
                ['warning_type_name' => $type['warning_type_name']],
                [
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]
            );
        }
    }
}
