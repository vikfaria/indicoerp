<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\JobLocation;
use Illuminate\Database\Seeder;

class DemoJobLocationSeeder extends Seeder
{
    public function run($userId): void
    {
        if (JobLocation::where('created_by', $userId)->exists()) {
            return;
        }

        $jobLocations = [
            ['name' => 'New York Headquarters', 'remote_work' => false, 'address' => '123 Broadway Street, Suite 500', 'city' => 'New York', 'state' => 'New York', 'country' => 'United States', 'postal_code' => '10001', 'status' => true],
            ['name' => 'San Francisco Office', 'remote_work' => false, 'address' => '456 Market Street, Floor 12', 'city' => 'San Francisco', 'state' => 'California', 'country' => 'United States', 'postal_code' => '94102', 'status' => true],
            ['name' => 'Remote Work - Global', 'remote_work' => true, 'address' => null, 'city' => null, 'state' => null, 'country' => 'Global', 'postal_code' => null, 'status' => true],
            ['name' => 'London Office', 'remote_work' => false, 'address' => '789 Oxford Street, 3rd Floor', 'city' => 'London', 'state' => 'England', 'country' => 'United Kingdom', 'postal_code' => 'W1C 1DX', 'status' => true],
            ['name' => 'Toronto Branch', 'remote_work' => false, 'address' => '321 King Street West, Suite 200', 'city' => 'Toronto', 'state' => 'Ontario', 'country' => 'Canada', 'postal_code' => 'M5V 1J5', 'status' => true],
            ['name' => 'Austin Tech Hub', 'remote_work' => false, 'address' => '654 South Congress Avenue', 'city' => 'Austin', 'state' => 'Texas', 'country' => 'United States', 'postal_code' => '78704', 'status' => true],
            ['name' => 'Remote Work - US Only', 'remote_work' => true, 'address' => null, 'city' => null, 'state' => null, 'country' => 'United States', 'postal_code' => null, 'status' => true],
            ['name' => 'Berlin Office', 'remote_work' => false, 'address' => 'Unter den Linden 77', 'city' => 'Berlin', 'state' => 'Berlin', 'country' => 'Germany', 'postal_code' => '10117', 'status' => false],
            ['name' => 'Chicago Office', 'remote_work' => false, 'address' => '875 North Michigan Avenue, Suite 3100', 'city' => 'Chicago', 'state' => 'Illinois', 'country' => 'United States', 'postal_code' => '60611', 'status' => true],
            ['name' => 'Sydney Office', 'remote_work' => false, 'address' => '200 George Street, Level 15', 'city' => 'Sydney', 'state' => 'New South Wales', 'country' => 'Australia', 'postal_code' => '2000', 'status' => true],
            ['name' => 'Tokyo Office', 'remote_work' => false, 'address' => '1-1-1 Shibuya, Shibuya City', 'city' => 'Tokyo', 'state' => 'Tokyo', 'country' => 'Japan', 'postal_code' => '150-0002', 'status' => true],
            ['name' => 'Singapore Office', 'remote_work' => false, 'address' => '1 Raffles Place, Tower 2, Level 61', 'city' => 'Singapore', 'state' => 'Singapore', 'country' => 'Singapore', 'postal_code' => '048616', 'status' => true],
            ['name' => 'Mumbai Office', 'remote_work' => false, 'address' => 'Bandra Kurla Complex, G Block', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India', 'postal_code' => '400051', 'status' => true],
            ['name' => 'Dubai Office', 'remote_work' => false, 'address' => 'Dubai International Financial Centre', 'city' => 'Dubai', 'state' => 'Dubai', 'country' => 'United Arab Emirates', 'postal_code' => '00000', 'status' => true],
            ['name' => 'Remote Work - Europe', 'remote_work' => true, 'address' => null, 'city' => null, 'state' => null, 'country' => 'Europe', 'postal_code' => null, 'status' => true],
            ['name' => 'Remote Work - Asia Pacific', 'remote_work' => true, 'address' => null, 'city' => null, 'state' => null, 'country' => 'Asia Pacific', 'postal_code' => null, 'status' => true],
            ['name' => 'Seattle Office', 'remote_work' => false, 'address' => '400 Dexter Avenue North', 'city' => 'Seattle', 'state' => 'Washington', 'country' => 'United States', 'postal_code' => '98109', 'status' => false],
            ['name' => 'Paris Office', 'remote_work' => false, 'address' => '75 Avenue des Champs-Élysées', 'city' => 'Paris', 'state' => 'Île-de-France', 'country' => 'France', 'postal_code' => '75008', 'status' => false]
        ];

        foreach ($jobLocations as $location) {
            JobLocation::create(array_merge($location, [
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
