<?php

namespace Workdo\Quotation\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class QuotationDatabaseSeeder extends Seeder
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
            (new DemoQuotationSeeder())->run($userId);
        }
    }
}