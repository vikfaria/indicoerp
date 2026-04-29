<?php

namespace Workdo\Coingate\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class CoingateDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            // Demo data seeders will be added here
        }
    }
}