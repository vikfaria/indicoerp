<?php

namespace Workdo\Hrm\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Award;
use Workdo\Hrm\Models\AwardType;
use Workdo\Hrm\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class DemoAwardSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Award::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        if (!empty($userId)) {
            $employees = User::whereIn('id', Employee::where('created_by', $userId)->pluck('user_id'))
                ->where('created_by', $userId)
                ->pluck('id')
                ->toArray();

            $awardTypes = AwardType::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($employees) || empty($awardTypes)) {
                return;
            }

            $descriptions = [
                'Outstanding performance excellence and exceptional leadership skills demonstrated consistently throughout the evaluation period.',
                'Innovation award for developing creative solutions that significantly improved operational efficiency and cost reduction.',
                'Customer service excellence recognition for maintaining highest satisfaction ratings and building strong client relationships.',
                'Team collaboration award for fostering positive work environment and successfully mentoring junior colleagues.',
                'Quality assurance excellence for maintaining zero-defect standards and implementing robust quality control processes.',
                'Sales achievement award for exceeding quarterly targets and establishing new market penetration strategies.',
                'Technical expertise recognition for mastering advanced technologies and providing innovative solutions to complex problems.',
                'Leadership development award for successfully managing cross-functional teams and driving organizational transformation initiatives.',
                'Process improvement recognition for streamlining workflows and implementing automation that enhanced productivity significantly.',
                'Safety excellence award for maintaining impeccable safety records and promoting workplace safety culture.',
                'Training and development recognition for creating comprehensive learning programs and knowledge transfer initiatives.',
                'Environmental sustainability award for implementing green practices and promoting corporate social responsibility programs.',
                'Digital transformation leadership for driving technology adoption and modernizing legacy systems across departments.',
                'Crisis management excellence for demonstrating exceptional problem-solving skills during challenging organizational situations.',
                'Vendor management recognition for negotiating favorable contracts and building strategic partnerships with suppliers.',
                'Data analytics excellence for providing actionable insights that drove informed business decision-making processes.',
                'Change management leadership for successfully guiding organizational adaptation and maintaining high employee engagement.',
                'International business development for expanding company presence in global markets and cultural adaptation.',
                'Supply chain optimization recognition for improving efficiency and reducing operational costs through strategic planning.',
                'Research and development excellence for breakthrough innovations and successful patent applications in technology.',
                'Financial management recognition for cost optimization initiatives and budget management that improved departmental performance.',
                'Compliance and governance excellence for ensuring regulatory requirements are consistently met across operations.',
                'Community engagement award for representing company values and building positive public relations in society.',
                'Diversity and inclusion leadership for creating inclusive workplace culture and promoting equal opportunities.',
                'Project management excellence for delivering critical business initiatives on time and within budget constraints.',
                'Strategic planning recognition for long-term vision development and successful implementation of business growth strategies.',
                'Communication excellence award for effective stakeholder management and clear information dissemination across organization.',
                'Mentorship and coaching recognition for developing talent pipeline and fostering professional growth of team members.',
                'Operational excellence award for maintaining high standards of service delivery and continuous improvement initiatives.',
                'Innovation in automation for implementing cutting-edge technologies that revolutionized traditional business processes effectively.'
            ];

            $awards = [];
            for ($i = 0; $i < 30; $i++) {
                $awardDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $awardDaysAgo - 2;

                $awards[] = [
                    'employee_id' => $employees[$i % count($employees)],
                    'award_type_id' => $awardTypes[$i % count($awardTypes)],
                    'award_date' => Carbon::now()->subDays($awardDaysAgo)->format('Y-m-d'),
                    'description' => $descriptions[$i % count($descriptions)],
                    'certificate' => 'award' . (($i % 4) + 1) . '.png',
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            shuffle($awards);
            foreach ($awards as $award) {
                Award::updateOrCreate(
                    [
                        'employee_id' => $award['employee_id'],
                        'award_date' => $award['award_date'],
                        'created_by' => $userId
                    ],
                    array_merge($award, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}
