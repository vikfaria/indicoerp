<?php

namespace Workdo\Recruitment\Database\Seeders;

use App\Models\User;
use Workdo\Recruitment\Models\CandidateAssessment;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\Candidate;


class DemoCandidateAssessmentSeeder extends Seeder
{
    public function run($userId): void
    {
        if (CandidateAssessment::where('created_by', $userId)->exists()) {
            return;
        }

        // Get candidates who have interview feedbacks with recommendation 'Strong Hire' (0) or 'Hire' (1)
        $eligibleCandidates = Candidate::where('created_by', $userId)
            ->whereHas('interviews.interviewFeedbacks', function($query) {
                $query->whereIn('recommendation', ['0', '1']);
            })
            ->get();

        if ($eligibleCandidates->isEmpty()) {
            return;
        }

        $users = User::emp()->where('created_by', $userId)->pluck('id')->toArray();
        if (empty($users)) {
            return;
        }

        $assessmentTypes = [
            'Technical Coding Test', 'System Design Assessment', 'Algorithm Challenge',
            'Database Design Test', 'Frontend Development Test', 'Backend API Test',
            'Problem Solving Assessment', 'Code Review Exercise', 'Architecture Design',
            'Security Assessment', 'Performance Optimization Test', 'Unit Testing Skills'
        ];

        $comments = [
            'Excellent problem-solving skills and clean code implementation.',
            'Good understanding of concepts but needs improvement in optimization.',
            'Strong technical knowledge with attention to detail.',
            'Adequate performance with room for growth in advanced topics.',
            'Outstanding analytical thinking and efficient solutions.',
            'Solid foundation but requires more practice with complex scenarios.',
            'Impressive debugging skills and code quality.',
            'Good effort but struggled with time management.',
            'Exceptional performance across all assessment criteria.',
            'Meets basic requirements with potential for development.'
        ];

        for ($i = 0; $i < min(20, $eligibleCandidates->count()); $i++) {
            $maxScore = fake()->randomElement([50, 75, 100, 120, 150]);
            $score = fake()->numberBetween(0, $maxScore);
            $percentage = ($score / $maxScore) * 100;

            $status = 1;
            if ($percentage >= 70) {
                $status = 0;
            } elseif ($percentage < 50) {
                $status = 2;
            }

            CandidateAssessment::create([
                'assessment_name' => fake()->randomElement($assessmentTypes),
                'score' => $score,
                'max_score' => $maxScore,
                'pass_fail_status' => $status,
                'comments' => fake()->randomElement($comments),
                'assessment_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                'candidate_id' => $eligibleCandidates->random()->id,
                'conducted_by' => fake()->randomElement($users),
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
