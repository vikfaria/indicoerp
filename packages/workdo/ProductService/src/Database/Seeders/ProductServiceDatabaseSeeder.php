<?php

namespace Workdo\ProductService\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ProductServiceDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);

        if(config('app.run_demo_seeder'))
        {
            $userId = User::where('email', 'company@example.com')->first()->id;

            (new DemoCategorySeeder())->run($userId);
            (new DemoTaxSeeder())->run($userId);
            (new DemoUnitSeeder())->run($userId);
            (new DemoProductServiceItemSeeder())->run($userId);
        }
    }
}
