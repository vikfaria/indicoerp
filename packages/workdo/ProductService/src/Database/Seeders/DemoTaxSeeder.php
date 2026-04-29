<?php

namespace Workdo\ProductService\Database\Seeders;

use Workdo\ProductService\Models\ProductServiceTax;
use Illuminate\Database\Seeder;

class DemoTaxSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $taxes = [
                ['tax_name' => 'GST', 'rate' => 18.00],
                ['tax_name' => 'VAT', 'rate' => 12.00],
                ['tax_name' => 'Service Tax', 'rate' => 15.00],
                ['tax_name' => 'Sales Tax', 'rate' => 8.50],
            ];

            foreach ($taxes as $tax) {
                ProductServiceTax::create([
                    'tax_name' => $tax['tax_name'],
                    'rate' => $tax['rate'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
