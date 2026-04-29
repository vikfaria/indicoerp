<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\InterviewRound;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\JobPosting;

class DemoInterviewRoundSeeder extends Seeder
{
    public function run($userId): void
    {
        if (InterviewRound::where('created_by', $userId)->exists()) {
            return;
        }

        $jobPostings = JobPosting::where('created_by', $userId)->where('is_published', 1)->where('status', 'active')->get();

        if ($jobPostings->isEmpty()) {
            return;
        }

        $interviewRounds = [
            ['name' => 'Initial Screening', 'description' => 'Initial phone/video screening to assess basic qualifications and cultural fit.'],
            ['name' => 'Technical Assessment', 'description' => 'Technical skills evaluation including coding challenges and problem-solving exercises.'],
            ['name' => 'Team Interview', 'description' => 'Interview with potential team members to assess collaboration and technical expertise.'],
            ['name' => 'Manager Interview', 'description' => 'Interview with hiring manager to discuss role expectations and career goals.'],
            ['name' => 'Final Interview', 'description' => 'Final interview with senior leadership to make the hiring decision.'],
            ['name' => 'HR Discussion', 'description' => 'HR discussion about compensation, benefits, and onboarding process.'],
            ['name' => 'Behavioral Interview', 'description' => 'Assessment of soft skills, communication, and behavioral competencies.'],
            ['name' => 'Portfolio Review', 'description' => 'Review of candidate portfolio, past work, and project discussions.'],
            ['name' => 'Case Study', 'description' => 'Practical case study or real-world problem-solving exercise.'],
            ['name' => 'Cultural Fit Assessment', 'description' => 'Evaluation of alignment with company values and culture.']
        ];

        foreach ($jobPostings as $jobPosting) {
            // Create 3-5 random interview rounds for each job posting
            $selectedRounds = collect($interviewRounds)->random(rand(3, 5));

            foreach ($selectedRounds as $index => $round) {
                InterviewRound::create([
                    'job_id' => $jobPosting->id,
                    'name' => $round['name'],
                    'sequence_number' => $index + 1,
                    'description' => $round['description'],
                    'status' => fake()->randomElement(['0', '1']),
                    'creator_id' => $userId,
                    'created_by' => $userId,
                ]);
            }
        }
    }
}
