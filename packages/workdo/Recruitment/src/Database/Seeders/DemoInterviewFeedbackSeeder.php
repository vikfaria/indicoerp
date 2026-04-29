<?php

namespace Workdo\Recruitment\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Recruitment\Models\InterviewFeedback;
use Workdo\Recruitment\Models\Interview;
use App\Models\User;

class DemoInterviewFeedbackSeeder extends Seeder
{
    public function run($userId): void
    {
        if (InterviewFeedback::where('created_by', $userId)->exists()) {
            return;
        }

        $interviews = Interview::with(['candidate', 'jobPosting'])
            ->where('created_by', $userId)
            ->where('status', '1') // Only completed interviews
            ->get();
        $employees = User::emp()->where('created_by', $userId)->get();

        if ($interviews->isEmpty() || $employees->isEmpty()) {
            return;
        }

        $feedbackData = [
            ['technical_rating' => 5, 'communication_rating' => 4, 'cultural_fit_rating' => 5, 'overall_rating' => 5, 'recommendation' => '0', 'strengths' => 'Exceptional problem-solving skills, deep understanding of system architecture, excellent coding practices', 'weaknesses' => 'Could improve presentation skills for client-facing scenarios', 'comments' => 'Outstanding candidate with strong technical foundation. Demonstrated excellent problem-solving approach and clean code implementation. Highly recommended for senior developer role.'],
            ['technical_rating' => 4, 'communication_rating' => 5, 'cultural_fit_rating' => 4, 'overall_rating' => 4, 'recommendation' => '1', 'strengths' => 'Excellent communication skills, strong leadership potential, innovative thinking', 'weaknesses' => 'Limited experience with cloud technologies, needs AWS training', 'comments' => 'Great candidate with excellent soft skills. Shows strong potential for growth and team leadership. Technical skills can be developed with proper training.'],
            ['technical_rating' => 3, 'communication_rating' => 4, 'cultural_fit_rating' => 4, 'overall_rating' => 4, 'recommendation' => '1', 'strengths' => 'Good communication skills, team player, eager to learn new technologies', 'weaknesses' => 'Limited experience with microservices architecture, needs mentoring on advanced concepts', 'comments' => 'Solid candidate with good fundamentals. Shows enthusiasm and willingness to learn. Would benefit from senior developer guidance.'],
            ['technical_rating' => 4, 'communication_rating' => 3, 'cultural_fit_rating' => 5, 'overall_rating' => 4, 'recommendation' => '1', 'strengths' => 'Strong technical skills, excellent problem-solving ability, great cultural fit', 'weaknesses' => 'Needs to work on public speaking and presentation skills', 'comments' => 'Technically strong candidate who aligns well with company values. Communication skills can be improved through training and practice.'],
            ['technical_rating' => 5, 'communication_rating' => 5, 'cultural_fit_rating' => 3, 'overall_rating' => 4, 'recommendation' => '1', 'strengths' => 'Outstanding technical expertise, excellent communication, proven track record', 'weaknesses' => 'May need time to adapt to company culture and processes', 'comments' => 'Highly skilled candidate with impressive background. Cultural adaptation should not be a major concern given their professional experience.'],
            ['technical_rating' => 3, 'communication_rating' => 3, 'cultural_fit_rating' => 3, 'overall_rating' => 3, 'recommendation' => '2', 'strengths' => 'Basic technical knowledge, punctual, follows instructions well', 'weaknesses' => 'Lacks initiative, limited problem-solving creativity, needs significant guidance', 'comments' => 'Average candidate with room for improvement. May work for junior positions with proper training and supervision.'],
            ['technical_rating' => 2, 'communication_rating' => 4, 'cultural_fit_rating' => 4, 'overall_rating' => 3, 'recommendation' => '2', 'strengths' => 'Excellent interpersonal skills, positive attitude, quick learner', 'weaknesses' => 'Technical skills below required level, limited hands-on experience', 'comments' => 'Candidate has great potential but needs significant technical development. Consider for junior role with intensive training program.'],
            ['technical_rating' => 4, 'communication_rating' => 2, 'cultural_fit_rating' => 3, 'overall_rating' => 3, 'recommendation' => '2', 'strengths' => 'Strong technical background, good analytical skills, detail-oriented', 'weaknesses' => 'Poor communication skills, difficulty explaining complex concepts', 'comments' => 'Technically competent but communication barriers may impact team collaboration. Consider role with minimal client interaction.'],
            ['technical_rating' => 2, 'communication_rating' => 3, 'cultural_fit_rating' => 2, 'overall_rating' => 2, 'recommendation' => '3', 'strengths' => 'Shows interest in technology, willing to relocate', 'weaknesses' => 'Significant gaps in technical knowledge, poor problem-solving approach, lacks relevant experience', 'comments' => 'Candidate needs substantial skill development before being ready for this role. Current technical level does not meet our requirements.'],
            ['technical_rating' => 1, 'communication_rating' => 2, 'cultural_fit_rating' => 1, 'overall_rating' => 1, 'recommendation' => '4', 'strengths' => 'Attended the interview on time', 'weaknesses' => 'Fundamental technical concepts unclear, poor communication, not aligned with company values', 'comments' => 'Unfortunately, candidate does not meet the basic requirements for this position. Significant skill gap and cultural misalignment observed.'],
            ['technical_rating' => 3, 'communication_rating' => 5, 'cultural_fit_rating' => 5, 'overall_rating' => 4, 'recommendation' => '1', 'strengths' => 'Outstanding communication and presentation skills, excellent cultural alignment, strong work ethic', 'weaknesses' => 'Technical knowledge needs improvement, limited experience with modern frameworks', 'comments' => 'Excellent cultural fit with strong communication skills. Technical gaps can be addressed through training and mentorship programs.'],
            ['technical_rating' => 5, 'communication_rating' => 3, 'cultural_fit_rating' => 4, 'overall_rating' => 4, 'recommendation' => '1', 'strengths' => 'Exceptional technical expertise, deep knowledge of algorithms and data structures, strong problem-solving', 'weaknesses' => 'Needs improvement in team communication and collaboration skills', 'comments' => 'Highly technical candidate with excellent problem-solving abilities. Would benefit from soft skills development and team collaboration training.'],
            ['technical_rating' => 4, 'communication_rating' => 4, 'cultural_fit_rating' => 2, 'overall_rating' => 3, 'recommendation' => '2', 'strengths' => 'Good technical skills, clear communication, professional demeanor', 'weaknesses' => 'Cultural misalignment, different work style preferences, may not fit team dynamics', 'comments' => 'Competent candidate with good skills but cultural fit concerns. May work better in different team environment or with cultural adaptation support.'],
            ['technical_rating' => 2, 'communication_rating' => 2, 'cultural_fit_rating' => 4, 'overall_rating' => 2, 'recommendation' => '3', 'strengths' => 'Good cultural fit, positive attitude, willing to learn', 'weaknesses' => 'Significant technical skill gaps, poor communication clarity, limited relevant experience', 'comments' => 'While candidate shows good cultural alignment, technical and communication skills are below requirements. Extensive training would be needed.'],
            ['technical_rating' => 1, 'communication_rating' => 1, 'cultural_fit_rating' => 2, 'overall_rating' => 1, 'recommendation' => '4', 'strengths' => 'Showed up for interview, basic computer literacy', 'weaknesses' => 'Severe technical knowledge gaps, very poor communication skills, unprofessional behavior', 'comments' => 'Candidate is not suitable for this position. Fundamental skills are missing and professional standards not met. Strong reject recommendation.']
        ];

        foreach ($interviews->take(12) as $index => $interview) {
            // Use interview's actual interviewers if available
            $interviewerIds = [];
            if ($interview->interviewer_ids) {
                $interviewerIds = is_array($interview->interviewer_ids) ? $interview->interviewer_ids : json_decode($interview->interviewer_ids, true);
            }

            // Fallback to random employees if no interviewers assigned
            if (empty($interviewerIds)) {
                $selectedEmployees = $employees->random(fake()->numberBetween(1, min(2, $employees->count())));
                $interviewerIds = $selectedEmployees->pluck('id')->toArray();
            }

            $feedbackTemplate = $feedbackData[$index % count($feedbackData)];

            InterviewFeedback::create([
                'interview_id' => $interview->id,
                'interviewer_ids' => $interviewerIds,
                'technical_rating' => $feedbackTemplate['technical_rating'],
                'communication_rating' => $feedbackTemplate['communication_rating'],
                'cultural_fit_rating' => $feedbackTemplate['cultural_fit_rating'],
                'overall_rating' => $feedbackTemplate['overall_rating'],
                'recommendation' => $feedbackTemplate['recommendation'],
                'strengths' => $feedbackTemplate['strengths'],
                'weaknesses' => $feedbackTemplate['weaknesses'],
                'comments' => $feedbackTemplate['comments'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}
