<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\DealStage;
use Illuminate\Database\Seeder;
use Workdo\Lead\Models\Pipeline;


class DemoDealStageSeeder extends Seeder
{
    public function run($userId): void
    {
        if (DealStage::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            $pipelines = Pipeline::where('created_by', $userId)->get();

            if ($pipelines->isEmpty()) {
                return;
            }

            // Pipeline-specific deal stages
            $pipelineStages = [
                'Marketing' => [
                    ['name' => 'Campaign Launch', 'order' => 1],
                    ['name' => 'Lead Generation', 'order' => 2],
                    ['name' => 'Nurturing', 'order' => 3],
                    ['name' => 'Qualification', 'order' => 4],
                    ['name' => 'Handoff', 'order' => 5]
                ],
                'Lead Qualification' => [
                    ['name' => 'Initial Contact', 'order' => 1],
                    ['name' => 'Needs Assessment', 'order' => 2],
                    ['name' => 'Solution Fit', 'order' => 3],
                    ['name' => 'Proposal Sent', 'order' => 4],
                    ['name' => 'Decision', 'order' => 5]
                ]
            ];

            foreach ($pipelines as $pipeline) {
                $stages = $pipelineStages[$pipeline->name] ?? [];
                if (!empty($stages)) {
                    foreach ($stages as $stageData) {
                        DealStage::create([
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