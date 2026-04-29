<?php

namespace Workdo\Slack\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SlackDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(NotificationsTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);
    }
}
