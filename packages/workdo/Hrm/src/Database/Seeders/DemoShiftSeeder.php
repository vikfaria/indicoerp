<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Shift;

class DemoShiftSeeder extends Seeder
{
    public function run($userId)
    {
        if (Shift::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $shifts = [
            [
                'shift_name' => 'Morning Shift',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'is_night_shift' => false,
                'creator_id' => $userId,
                'created_by' => $userId,
            ],
            [
                'shift_name' => 'Evening Shift',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'break_start_time' => '18:00:00',
                'break_end_time' => '19:00:00',
                'is_night_shift' => false,
                'creator_id' => $userId,
                'created_by' => $userId,
            ],
            [
                'shift_name' => 'Night Shift',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'break_start_time' => '02:00:00',
                'break_end_time' => '03:00:00',
                'is_night_shift' => true,
                'creator_id' => $userId,
                'created_by' => $userId,
            ],
            [
                'shift_name' => 'Early Morning Shift',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'break_start_time' => '10:00:00',
                'break_end_time' => '11:00:00',
                'is_night_shift' => false,
                'creator_id' => $userId,
                'created_by' => $userId,
            ],
            [
                'shift_name' => 'Flexible Shift',
                'start_time' => '10:00:00',
                'end_time' => '18:00:00',
                'break_start_time' => '13:00:00',
                'break_end_time' => '14:00:00',
                'is_night_shift' => false,
                'creator_id' => $userId,
                'created_by' => $userId,
            ],
            [
                'shift_name' => 'Weekend Shift',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'break_start_time' => '12:00:00',
                'break_end_time' => '13:00:00',
                'is_night_shift' => false,
                'creator_id' => $userId,
                'created_by' => $userId,
            ],
        ];

        foreach ($shifts as $shift) {
            Shift::updateOrCreate(
                ['shift_name' => $shift['shift_name'], 'created_by' => $userId],
                $shift
            );
        }
    }
}