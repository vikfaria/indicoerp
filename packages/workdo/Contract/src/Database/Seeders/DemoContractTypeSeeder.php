<?php

namespace Workdo\Contract\Database\Seeders;

use Workdo\Contract\Models\ContractType;
use Illuminate\Database\Seeder;



class DemoContractTypeSeeder extends Seeder
{
    public function run($userId): void
    {
        if (ContractType::where('created_by', $userId)->exists()) {
            return;
        }

        $contractTypes = [
            'Software Development Agreement',
            'IT Services Contract',
            'Cloud Services Agreement',
            'SaaS Subscription Contract',
            'Digital Marketing Agreement',
            'Consulting Services Contract',
            'Maintenance & Support Agreement',
            'Non-Disclosure Agreement (NDA)',
            'Master Service Agreement (MSA)',
            'Statement of Work (SOW)',
            'Software License Agreement',
            'Data Processing Agreement',
            'Vendor Service Agreement',
            'Professional Services Contract',
            'Technology Partnership Agreement',
            'API License Agreement',
            'Hosting Services Contract',
            'Security Services Agreement',
            'Training & Education Contract',
            'Business Process Outsourcing (BPO)'
        ];

        foreach ($contractTypes as $index => $typeName) {
            ContractType::create([
                'name' => $typeName,
                'is_active' => $index < 18,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}