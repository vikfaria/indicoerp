<?php

namespace Workdo\Goal\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class GoalDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            // Add here your demo data seeders
            $userId = User::where('email', 'company@example.com')->first()->id;
            (new DemoCategorySeeder())->run($userId);
            (new DemoGoalSeeder())->run($userId);
            (new DemoMilestoneSeeder())->run($userId);
            (new DemoContributionSeeder())->run($userId);
            (new DemoTrackingSeeder())->run($userId);

        }
    }
}
