<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Source;
use Workdo\Lead\Models\Lead;
use Workdo\Lead\Models\Deal;
use Workdo\ProductService\Models\ProductServiceItem;
use Illuminate\Database\Seeder;

class DemoLeadProductSourceSeeder extends Seeder
{
    public function run($userId): void
    {
        if (!empty($userId)) {
            $sourceIds = Source::where('created_by', $userId)->pluck('id')->toArray();
            $productIds = [];

            if (Module_is_active('ProductService')) {
                $productIds = ProductServiceItem::where('created_by', $userId)->pluck('id')->toArray();
            }

            // Add random sources/products to existing assignments for leads
            $leads = Lead::where('created_by', $userId)->get();

            if ($leads->isEmpty()) {
                return;
            }

            foreach ($leads as $lead) {
                if (!$lead) {
                    continue;
                }
                if (!empty($sourceIds)) {
                    $existing = $lead->sources ? explode(',', $lead->sources) : [];
                    $new = $this->getRandomItems($sourceIds);
                    if (!empty($new)) {
                        $lead->sources = implode(',', array_unique(array_merge($existing, $new)));
                    }
                }
                if (!empty($productIds)) {
                    $existing = $lead->products ? explode(',', $lead->products) : [];
                    $new = $this->getRandomItems($productIds);
                    if (!empty($new)) {
                        $lead->products = implode(',', array_unique(array_merge($existing, $new)));
                    }
                }
                if ($lead->isDirty()) {
                    $lead->save();
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