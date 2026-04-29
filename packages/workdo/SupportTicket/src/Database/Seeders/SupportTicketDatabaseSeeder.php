<?php

namespace Workdo\SupportTicket\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SupportTicketDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        $this->call(EmailTemplateTableSeeder::class);
        $this->call(NotificationsTableSeeder::class);
        $this->call(DefultSettingTableSeeder::class);
        $this->call(PermissionTableSeeder::class);

        if (config('app.run_demo_seeder')) {
            // Add here your demo data seeders
            $userId = User::where('email', 'company@example.com')->first()->id;
            (new DemoSystemSetupSeeder())->run($userId);
            (new DemoTicketSeeder())->run($userId);
            (new DemoKnowledgeBaseSeeder())->run($userId);
            (new DemoFaqSeeder())->run($userId);
            (new DemoContactSeeder())->run($userId);
            (new DemoSupportTicketSettingsSeeder())->run($userId);
        }
    }
}





































































































