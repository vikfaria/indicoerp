<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\CandidateSources;
use Illuminate\Database\Seeder;



class DemoCandidateSourcesSeeder extends Seeder
{
    public function run($userId): void
    {
        if (CandidateSources::where('created_by', $userId)->exists()) {
            return;
        }

        $sources = [
            [
                'name' => 'LinkedIn',
                'description' => 'Professional networking platform for recruiting candidates',
                'is_active' => true
            ],
            [
                'name' => 'Indeed',
                'description' => 'Popular job board for posting positions and finding candidates',
                'is_active' => true
            ],
            [
                'name' => 'Employee Referral',
                'description' => 'Candidates referred by current employees',
                'is_active' => true
            ],
            [
                'name' => 'Company Website',
                'description' => 'Direct applications through company career page',
                'is_active' => true
            ],
            [
                'name' => 'Glassdoor',
                'description' => 'Job board with company reviews and salary information',
                'is_active' => true
            ],
            [
                'name' => 'Monster',
                'description' => 'Online job board and career platform',
                'is_active' => false
            ],
            [
                'name' => 'Recruitment Agency',
                'description' => 'External recruitment firms and headhunters',
                'is_active' => true
            ],
            [
                'name' => 'University Campus',
                'description' => 'Campus recruitment and job fairs at universities',
                'is_active' => true
            ],
            [
                'name' => 'Social Media',
                'description' => 'Facebook, Twitter, Instagram job postings',
                'is_active' => false
            ],
            [
                'name' => 'Walk-in',
                'description' => 'Candidates who apply directly at office location',
                'is_active' => false
            ]
        ];

        foreach ($sources as $source) {
            CandidateSources::create([
                'name' => $source['name'],
                'description' => $source['description'],
                'is_active' => $source['is_active'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
