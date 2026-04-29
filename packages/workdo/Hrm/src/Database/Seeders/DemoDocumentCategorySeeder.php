<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\DocumentCategory;
use Illuminate\Database\Seeder;



class DemoDocumentCategorySeeder extends Seeder
{
    public function run($userId): void
    {
        if (DocumentCategory::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        $documentTypes = [
            'Identity Documents',
            'Educational Certificates',
            'Employment Records',
            'Medical Records',
            'Financial Documents',
            'Legal Documents',
            'Insurance Papers',
            'Tax Documents',
            'Property Documents',
            'Vehicle Documents',
            'Travel Documents',
            'Professional Licenses',
            'Training Certificates',
            'Performance Reviews',
            'Contract Documents'
        ];

        foreach ($documentTypes as $type) {
            DocumentCategory::updateOrCreate(
                ['document_type' => $type, 'created_by' => $userId],
                [
                    'status' => true,
                    'creator_id' => $userId
                ]
            );
        }
    }
}