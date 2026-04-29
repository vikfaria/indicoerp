<?php

namespace Workdo\Account\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Account\Models\Customer;
use App\Models\User;
use Faker\Factory as Faker;

class DemoCustomerDatabaseSeeder extends Seeder
{
    public function run($userId = null)
    {
        if (!$userId) {
            return;
        }

        $faker = Faker::create();

        $clientUsers = User::where('created_by',$userId)->where('type', 'client')->get();
        $usedUserIds = Customer::where('created_by',$userId)->pluck('user_id')->toArray();
        $availableUsers = $clientUsers->whereNotIn('id', $usedUserIds);

        if ($availableUsers->isEmpty()) {
            return;
        }
        for ($i = 0; $i < 15; $i++) {
            $clientUser = $availableUsers->shift();
            if (!$clientUser) {
                break;
            }
            Customer::create([
                'user_id' => $clientUser?->id,
                'company_name' => $faker->company,
                'contact_person_name' => $faker->name,
                'contact_person_email' => $faker->companyEmail,
                'contact_person_mobile' => '+' . $faker->numberBetween(1, 999) . $faker->numerify('##########'),
                'tax_number' => $faker->numerify('TAX-########'),
                'payment_terms' => $faker->randomElement(['Net 30', 'Net 60', 'COD', 'Net 15']),
                'billing_address' => [
                    'name' => $faker->name,
                    'address_line_1' => $faker->streetAddress,
                    'address_line_2' => $faker->optional()->secondaryAddress,
                    'city' => $faker->city,
                    'state' => $faker->state,
                    'country' => $faker->country,
                    'zip_code' => $faker->postcode,
                ],
                'shipping_address' => [
                    'name' => $faker->name,
                    'address_line_1' => $faker->streetAddress,
                    'address_line_2' => $faker->optional()->secondaryAddress,
                    'city' => $faker->city,
                    'state' => $faker->state,
                    'country' => $faker->country,
                    'zip_code' => $faker->postcode,
                ],
                'same_as_billing' => $faker->boolean(30),
                'notes' => $faker->optional()->sentence,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}