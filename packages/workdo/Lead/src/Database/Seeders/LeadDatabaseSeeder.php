<?php

namespace Workdo\Lead\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class LeadDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);
        $this->call(EmailTemplatesSeeder::class);
        $this->call(NotificationsTableSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            // Add here your demo data seeders
            $userId = User::where('email', 'company@example.com')->first()->id;
            (new DemoPipelineSeeder())->run($userId);
            (new DemoLeadStageSeeder())->run($userId);
            (new DemoDealStageSeeder())->run($userId);
            (new DemoLabelSeeder())->run($userId);
            (new DemoSourceSeeder())->run($userId);
            (new DemoLeadSeeder())->run($userId);
            (new DemoLeadTaskSeeder())->run($userId);
            (new DemoUserLeadSeeder())->run($userId);
            (new DemoLeadCallSeeder())->run($userId);
            (new DemoLeadProductSourceSeeder())->run($userId);
            (new DemoLeadEmailDiscussionSeeder())->run($userId);
            (new DemoDealSeeder())->run($userId);
            (new DemoDealTaskSeeder())->run($userId);
            (new DemoUserDealSeeder())->run($userId);
            (new DemoClientDealSeeder())->run($userId);
            (new DemoDealCallSeeder())->run($userId);
            (new DemoDealProductSourceSeeder())->run($userId);
            (new DemoDealEmailDiscussionSeeder())->run($userId);
            (new DemoCallActivitySeeder())->run($userId);
        }
    }
}
