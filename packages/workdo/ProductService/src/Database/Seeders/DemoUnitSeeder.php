<?php

namespace Workdo\ProductService\Database\Seeders;

use Workdo\ProductService\Models\ProductServiceUnit;
use Illuminate\Database\Seeder;

class DemoUnitSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $units = [
                // Electronics & Technology
                'Piece',
                'Set',
                'Unit',
                'Pack',
                
                // Fashion & Apparel
                'Pair',
                'Dozen',
                'Size',
                'Bundle',
                
                // Books & Stationery
                'Book',
                'Ream',
                'Sheet',
                'Notebook',
                
                // Home & Garden
                'Pot',
                'Bag',
                'Packet',
                'Meter',
                'Centimeter',
                'Millimeter',
                'Inch',
                'Foot',
                
                // Sports & Fitness
                'Equipment',
                'Ball',
                'Kit',
                
                // Health & Beauty
                'Bottle',
                'Tube',
                'Jar',
                'Milliliter',
                
                // Fruits & Vegetables
                'Kilogram',
                'Gram',
                'Basket',
                'Crate',
                'Ton',
                
                // Food & Beverages
                'Liter',
                'Can',
                'Box',
                'Carton',
                'Gallon',
                
                // Toys & Games
                'Toy',
                'Game',
                'Puzzle',
                
                // Jewelry & Accessories
                'Carat',
                'Ounce',
                
                // Service Units (Income/Expense)
                'Hour',
                'Day',
                'Month',
                'Year',
                'Session',
                'Service Call',
            ];
            
            foreach ($units as $unit) {
                ProductServiceUnit::create([
                    'unit_name' => $unit,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}