<?php

namespace Workdo\SupportTicket\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Workdo\SupportTicket\Models\TicketField;

class DefultSettingTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $super_admin = User::where('type', 'super admin')->first();
        if (!empty($super_admin)) {
            $companies = User::where('type', 'company')->get();
            foreach ($companies as $company) {
                TicketField::defaultdata($company->id);
            }
        }
    }
}