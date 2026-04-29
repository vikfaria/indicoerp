<?php

namespace Workdo\Lead\Database\Seeders;

use Workdo\Lead\Models\Label;
use Illuminate\Database\Seeder;
use Workdo\Lead\Models\Pipeline;

class DemoLabelSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Label::where('created_by', $userId)->exists()) {
            return;
        }
        if (!empty($userId)) {
            $pipelines = Pipeline::where('created_by', $userId)->get();

            if ($pipelines->isEmpty()) {
                return;
            }

            $labelsByPipeline = [
                'Marketing' => [
                    ['name' => 'First Visit', 'color' => '#EF4444'],
                    ['name' => 'Return Visitor', 'color' => '#F97316'],
                    ['name' => 'Content Downloaded', 'color' => '#3B82F6'],
                    ['name' => 'Form Submitted', 'color' => '#10b77f'],
                    ['name' => 'MQL Ready', 'color' => '#8B5CF6']
                ],
                'Lead Qualification' => [
                    ['name' => 'High Priority', 'color' => '#EF4444'],
                    ['name' => 'Medium Priority', 'color' => '#F97316'],
                    ['name' => 'Low Priority', 'color' => '#3B82F6'],
                    ['name' => 'Follow Up', 'color' => '#10b77f'],
                    ['name' => 'Not Interested', 'color' => '#6B7280']
                ],
                'Sales' => [
                    ['name' => 'New Lead', 'color' => '#EF4444'],
                    ['name' => 'Contacted', 'color' => '#F97316'],
                    ['name' => 'Qualified', 'color' => '#3B82F6'],
                    ['name' => 'Proposal Sent', 'color' => '#10b77f'],
                    ['name' => 'Closed Deal', 'color' => '#6B7280']
                ],
            ];

            foreach ($pipelines as $pipeline) {
                $labels = $labelsByPipeline[$pipeline->name] ?? [];
                if (!empty($labels)) {
                    foreach ($labelsByPipeline[$pipeline->name] as $labelData) {
                        Label::create([
                            'name' => $labelData['name'],
                            'color' => $labelData['color'],
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
