<?php

namespace Workdo\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class TrainingDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
        $this->call(MarketplaceSettingSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            $userId = User::where('email', 'company@example.com')->first()->id;

            (new TrainingTypeDemoSeeder())->run($userId);
            (new TrainerDemoSeeder())->run($userId);
            (new TrainingDemoSeeder())->run($userId);
            (new TrainingTaskDemoSeeder())->run($userId);
            (new TrainingFeedbackDemoSeeder())->run($userId);
        }
    }
}