<?php

namespace Workdo\Twilio\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class TwilioDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);
        $this->call(NotificationsTableSeeder::class);

        if (config('app.run_demo_seeder')) {
            // Add here your demo data seeders
            $userId = User::where('email', 'company@example.com')->first()->id;
        }
    }
}
