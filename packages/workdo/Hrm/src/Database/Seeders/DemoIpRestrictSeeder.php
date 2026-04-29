<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\IpRestrict;
use Illuminate\Database\Seeder;



class DemoIpRestrictSeeder extends Seeder
{
    public function run($userId): void
    {
        
        if (IpRestrict::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }

        $ips = [
            '192.168.1.100',
            '192.168.1.101',
            '10.0.0.50',
            '10.0.0.51',
            '172.16.0.10',
            '203.0.113.25',
            '198.51.100.30',
            '192.0.2.15'
        ];

        foreach ($ips as $ip) {
            IpRestrict::updateOrCreate(
                ['ip' => $ip, 'created_by' => $userId],
                [
                    'creator_id' => $userId
                ]
            );
        }
    }
}