<?php

namespace Workdo\Recruitment\Database\Seeders;

use Workdo\Recruitment\Models\Interview;
use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\InterviewRound;
use Workdo\Recruitment\Models\InterviewType;
use App\Models\User;


class DemoInterviewSeeder extends Seeder
{
    public function run($userId): void
    {
        if (Interview::where('created_by', $userId)->exists()) {
            return;
        }

        $candidates = Candidate::where('created_by', $userId)->where('status', '2')->get();
        $jobPostings = JobPosting::where('created_by', $userId)->where('is_published', 1)->where('status', 'active')->get();
        $interviewRounds = InterviewRound::where('created_by', $userId)->where('status', 0)->get();
        $interviewTypes = InterviewType::where('created_by', $userId)->where('is_active', 1)->get();
        $employees = User::emp()->where('created_by', $userId)->get();

        if ($candidates->isEmpty() || $jobPostings->isEmpty() || $interviewRounds->isEmpty() || $interviewTypes->isEmpty()) {
            return;
        }

        $interviewData = [
            [
                'location' => 'Conference Room A',
                'meeting_link' => null,
                'duration' => 90,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'HR Office',
                'meeting_link' => null,
                'duration' => 45,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://meet.google.com/xyz-abc-def',
                'duration' => 60,
                'status' => '0', // Scheduled
                'feedback_submitted' => false
            ],
            [
                'location' => 'Conference Room B',
                'meeting_link' => null,
                'duration' => 120,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://zoom.us/j/123456789',
                'duration' => 75,
                'status' => '0', // Scheduled
                'feedback_submitted' => false
            ],
            [
                'location' => 'Executive Conference Room',
                'meeting_link' => null,
                'duration' => 60,
                'status' => '2', // Cancelled
                'feedback_submitted' => false
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/abc123',
                'duration' => 90,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'Conference Room C',
                'meeting_link' => null,
                'duration' => 45,
                'status' => '0', // Scheduled
                'feedback_submitted' => false
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://meet.google.com/system-design-123',
                'duration' => 120,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'Manager Office',
                'meeting_link' => null,
                'duration' => 60,
                'status' => '3', // No-show
                'feedback_submitted' => false
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://meet.google.com/tech-round-456',
                'duration' => 90,
                'status' => '0', // Scheduled
                'feedback_submitted' => false
            ],
            [
                'location' => 'Conference Room D',
                'meeting_link' => null,
                'duration' => 60,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://zoom.us/j/987654321',
                'duration' => 45,
                'status' => '2', // Cancelled
                'feedback_submitted' => false
            ],
            [
                'location' => 'Training Room',
                'meeting_link' => null,
                'duration' => 120,
                'status' => '1', // Completed
                'feedback_submitted' => true
            ],
            [
                'location' => 'Online',
                'meeting_link' => 'https://teams.microsoft.com/l/meetup-join/xyz789',
                'duration' => 75,
                'status' => '0', // Scheduled
                'feedback_submitted' => false
            ]
        ];

        foreach ($interviewData as $index => $data) {
            $candidate = $candidates->random();
            $candidateJobId = $candidate ? $candidate->job_id : $jobPostings->random()?->id;

            // Get interview rounds for this specific job
            $jobInterviewRounds = $interviewRounds->where('job_id', $candidateJobId);
            $selectedRound = $jobInterviewRounds->isNotEmpty() ? $jobInterviewRounds->random() : $interviewRounds->random();

            // Select 1-3 random employees as interviewers
            $selectedEmployees = $employees->random(fake()->numberBetween(1, min(3, $employees->count())));
            $interviewerIds = $selectedEmployees->pluck('id')->map(fn($id) => (string) $id)->toArray();

            // Generate realistic dates
            $scheduledDate = fake()->dateTimeBetween('-10 days', '+20 days')->format('Y-m-d');
            $scheduledTime = fake()->randomElement(['09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00']);

            Interview::create([
                'scheduled_date' => $scheduledDate,
                'scheduled_time' => $scheduledTime,
                'duration' => $data['duration'],
                'location' => $data['location'],
                'meeting_link' => $data['meeting_link'],
                'interviewer_ids' => $interviewerIds,
                'status' => $data['status'],
                'feedback_submitted' => $data['feedback_submitted'],
                'candidate_id' => $candidate?->id,
                'job_id' => $candidateJobId,
                'round_id' => $selectedRound?->id,
                'interview_type_id' => $interviewTypes->random()?->id,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
