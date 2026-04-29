<?php

namespace Workdo\LandingPage\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\LandingPage\Models\NewsletterSubscriber;
use Faker\Factory as Faker;

class DemoNewsletterSubscriberSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 50; $i++) {
            $subscribedAt = $faker->dateTimeBetween('-6 months', 'now');
            
            NewsletterSubscriber::create([
                'email' => $faker->unique()->safeEmail,
                'subscribed_at' => $subscribedAt,
                'ip_address' => $faker->ipv4,
                'country' => $faker->country,
                'city' => $faker->city,
                'region' => $faker->state,
                'country_code' => $faker->countryCode,
                'isp' => $faker->company . ' ISP',
                'org' => $faker->company,
                'timezone' => $faker->timezone,
                'latitude' => $faker->latitude,
                'longitude' => $faker->longitude,
                'user_agent' => $faker->userAgent,
                'browser' => $faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'os' => $faker->randomElement(['Windows 10', 'macOS', 'Linux', 'Android', 'iOS']),
                'device' => $faker->randomElement(['Desktop', 'Mobile', 'Tablet']),
                'created_at' => $subscribedAt,
                'updated_at' => $subscribedAt,
            ]);
        }
    }
}