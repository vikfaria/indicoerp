<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\TerminationType;
use Illuminate\Database\Seeder;



class DemoTerminationTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (TerminationType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $terminationTypes = [
            ['termination_type' => 'Voluntary Resignation'],
            ['termination_type' => 'Retirement'],
            ['termination_type' => 'End of Contract'],
            ['termination_type' => 'Layoff'],
            ['termination_type' => 'Misconduct'],
            ['termination_type' => 'Job Abandonment'],
            ['termination_type' => 'Performance Issues'],
            ['termination_type' => 'Mutual Agreement'],
            ['termination_type' => 'Redundancy'],
            ['termination_type' => 'Medical Reasons'],
            ['termination_type' => 'Company Closure'],
            ['termination_type' => 'Policy Violation'],
            ['termination_type' => 'Workplace Harassment'],
            ['termination_type' => 'Insubordination'],
            ['termination_type' => 'Conflict of Interest'],
            ['termination_type' => 'Probation Termination'],
            ['termination_type' => 'Position Elimination'],
            ['termination_type' => 'Relocation'],
            ['termination_type' => 'Retrenchment'],
            ['termination_type' => 'Unethical Behavior'],
        ];

        foreach ($terminationTypes as $type) {
            TerminationType::updateOrCreate(
                ['termination_type' => $type['termination_type']], // Unique condition
                [
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]
            );
        }
    }
}
