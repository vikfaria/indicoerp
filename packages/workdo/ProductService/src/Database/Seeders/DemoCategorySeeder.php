<?php

namespace Workdo\ProductService\Database\Seeders;

use Workdo\ProductService\Models\ProductServiceCategory;
use Illuminate\Database\Seeder;

class DemoCategorySeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $categories = [
                // Item Categories
                ['name' => 'Electronics & Technology', 'color' => '#3B82F6'],
                ['name' => 'Fashion & Apparel', 'color' => '#efe444'],
                ['name' => 'Books & Stationery', 'color' => '#10b77f'],
                ['name' => 'Home & Garden', 'color' => '#F59E0B'],
                ['name' => 'Sports & Fitness', 'color' => '#8B5CF6'],
                ['name' => 'Health & Beauty', 'color' => '#EC4899'],
                ['name' => 'Fruits & Vegetables', 'color' => '#6B7280'],
                ['name' => 'Food & Beverages', 'color' => '#F97316'],
                ['name' => 'Automotive & Tools', 'color' => '#06B6D4'],
                ['name' => 'Jewelry & Accessories', 'color' => '#84CC16'],
            ];

            if (!empty($categories)) {
                $categories = collect($categories)->shuffle()->values()->toArray(); // random select from array
            }

            foreach ($categories as $category) {
                ProductServiceCategory::create([
                    'name' => $category['name'],
                    'color' => $category['color'],
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
