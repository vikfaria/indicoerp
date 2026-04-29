<?php

namespace Workdo\Recruitment\Database\Seeders;

use App\Models\User;
use Workdo\Recruitment\Models\CandidateOnboarding;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\OnboardingChecklist;


class DemoCandidateOnboardingSeeder extends Seeder
{
    public function run($userId): void
    {
        if (CandidateOnboarding::where('created_by', $userId)->exists()) {
            return;
        }

        $candidates = Candidate::where('created_by', $userId)->where('status', 4)->get();
        $checklists = OnboardingChecklist::where('created_by', $userId)->where('status', 1)->get();
        $users = User::emp()->where('created_by', $userId)->get();

        if ($candidates->isEmpty() || $checklists->isEmpty() || $users->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 15; $i++) {
            // Realistic start dates - mostly recent hires
            $startDate = fake()->dateTimeBetween('-60 days', '+14 days');

            // Status based on start date logic
            $status = 'Pending';
            if ($startDate < now()->subDays(30)) {
                $status = 'Completed';
            } elseif ($startDate < now()->addDays(7)) {
                $status = fake()->randomElement(['In Progress', 'Completed']);
            }

            CandidateOnboarding::create([
                'candidate_id' => $candidates->random()->id,
                'checklist_id' => $checklists->random()->id,
                'start_date' => $startDate->format('Y-m-d'),
                'buddy_employee_id' => $users->random()->id,
                'status' => $status,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
