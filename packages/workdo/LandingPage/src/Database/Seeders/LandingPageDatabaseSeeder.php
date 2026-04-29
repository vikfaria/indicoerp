<?php

namespace Workdo\LandingPage\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class LandingPageDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(LandingPageSettingSeeder::class);
        $this->call(CustomPageSeeder::class);
        // $this->call(MarketplaceSettingSeeder::class);
        if(config('app.run_demo_seeder'))
        {
            $this->call(DemoNewsletterSubscriberSeeder::class);
        }
    }
}
