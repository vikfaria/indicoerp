<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Source;
use Illuminate\Database\Seeder;

class DemoSourceSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Source::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) 
        {
            $sources = [
                'Website Contact Form',
                'Social Media Marketing',
                'Email Marketing',
                'Referral Program',
                'Cold Calling',
                'Google Ads Campaign',
                'Trade Show Events',
                'LinkedIn Outreach',
                'Content Marketing',
                'Partner Referral',
                'Direct Mail Campaign',
                'Webinar Registration',
                'SEO Organic Search',
                'Industry Publication',
                'Networking Events',
            ];
            
            foreach ($sources as $name) {
                Source::create([
                    'name' => $name,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}