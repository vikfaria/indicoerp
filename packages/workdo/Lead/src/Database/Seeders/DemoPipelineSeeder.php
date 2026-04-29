<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Pipeline;
use Illuminate\Database\Seeder;

class DemoPipelineSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Pipeline::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId))
        {
            $pipelines = [
                'Marketing',
                'Lead Qualification'
            ];

            foreach ($pipelines as $name) {
                Pipeline::create([
                    'name' => $name,
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
