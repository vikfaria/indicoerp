<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Source;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\Deal;
use Workdo\ProductService\Models\ProductServiceItem;
use Illuminate\Database\Seeder;

class DemoDealProductSourceSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $sourceIds = Source::where('created_by', $userId)->pluck('id')->toArray();
            $productIds = [];

            if (Module_is_active('ProductService')) {
                $productIds = ProductServiceItem::where('created_by', $userId)->pluck('id')->toArray();
            }

            // Only assign to deals that are NOT converted from leads
            $convertedDealIds = Lead::where('created_by', $userId)->where('is_converted', '>', 0)->pluck('is_converted')->toArray();

            $deals = Deal::where('created_by', $userId)->whereNotIn('id', $convertedDealIds)->get();
            
            if ($deals->isEmpty()) {
                return;
            }
            
            foreach ($deals as $deal) {
                if (!$deal) {
                    continue;
                }
                if (!empty($sourceIds)) {
                    $existing = is_array($deal->sources) ? $deal->sources : [];
                    $new = $this->getRandomItems($sourceIds);
                    if (!empty($new)) {
                        $deal->sources = array_values(array_unique(array_merge($existing, $new)));
                    }
                }
                if (!empty($productIds)) {
                    $existing = is_array($deal->products) ? $deal->products : [];
                    $new = $this->getRandomItems($productIds);
                    if (!empty($new)) {
                        $deal->products = array_values(array_unique(array_merge($existing, $new)));
                    }
                }
                if ($deal->isDirty()) {
                    $deal->save();
                }
            }
        }
    }

    private function getRandomItems(array $items): array
    {
        if (empty($items)) {
            return [];
        }
        $count = rand(1, min(2, count($items)));
        $randomKeys = (array) array_rand($items, $count);
        return array_map(fn($key) => $items[$key], $randomKeys);
    }
}