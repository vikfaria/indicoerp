<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\LeadStage;
use Illuminate\Database\Seeder;
use Workdo\Lead\Models\Pipeline;


class DemoLeadStageSeeder extends Seeder
{
    public function run($userId): void
    {
        if (LeadStage::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            $pipelines = Pipeline::where('created_by', $userId)->get();

            if ($pipelines->isEmpty()) {
                return;
            }

            // Pipeline-specific lead stages following LeadUtility pattern
            $pipelineStages = [
                'Marketing' => [
                    ['name' => 'Prospect', 'order' => 1],
                    ['name' => 'Contacted', 'order' => 2],
                    ['name' => 'Engaged', 'order' => 3],
                    ['name' => 'Qualified', 'order' => 4],
                    ['name' => 'Converted', 'order' => 5]
                ],
                'Lead Qualification' => [
                    ['name' => 'Unqualified', 'order' => 1],
                    ['name' => 'In Review', 'order' => 2],
                    ['name' => 'Qualified', 'order' => 3],
                    ['name' => 'Approved', 'order' => 4],
                    ['name' => 'Rejected', 'order' => 5]
                ]
            ];

            foreach ($pipelines as $pipeline) {
                $stages = $pipelineStages[$pipeline->name] ?? [];
                if (!empty($stages)) {
                    foreach ($stages as $stageData) {
                        LeadStage::create([
                            'name' => $stageData['name'],
                            'order' => $stageData['order'],
                            'pipeline_id' => $pipeline->id,
                            'creator_id' => $userId,
                            'created_by' => $userId,
                        ]);
                    }
                }
            }
        }
    }
}