<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\HolidayType;
use Illuminate\Database\Seeder;



class DemoHolidayTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (HolidayType::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $holidayTypes = [
            'National Holiday',
            'Religious Holiday',
            'Company Holiday',
            'Public Holiday',
            'Federal Holiday',
            'State Holiday',
            'Local Holiday',
            'Cultural Holiday',
            'Seasonal Holiday',
            'Memorial Holiday',
            'Independence Holiday',
            'Festival Holiday',
            'Traditional Holiday',
            'International Holiday',
            'Regional Holiday'
        ];

        foreach ($holidayTypes as $type) {
            HolidayType::updateOrCreate(
                ['holiday_type' => $type, 'created_by' => $userId],
                ['creator_id' => $userId]
            );
        }
    }
}