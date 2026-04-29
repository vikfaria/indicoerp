<?php

namespace Workdo\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Training\Models\TrainingFeedback;
use Workdo\Training\Models\TrainingTask;
use Carbon\Carbon;

class TrainingFeedbackDemoSeeder extends Seeder
{
    public function run($userId)
    {
        if (TrainingFeedback::where('created_by', $userId)->exists()) {
            return;
        }
        $tasks = TrainingTask::with('training')->get();
        
        if ($tasks->isEmpty()) {
            return;
        }
        
        $users = \App\Models\User::emp()->where('created_by', 2)->select('id', 'name')->get();
        $userIds = $users->pluck('id')->toArray();
        if (empty($userIds)) {
            $userIds = [2];
        }
        
        $feedbackTemplates = [
            [
                'rating' => 5,
                'comments' => 'Excellent training session with comprehensive coverage of all topics. The practical exercises were particularly helpful in understanding complex concepts and applying them effectively.',
            ],
            [
                'rating' => 4,
                'comments' => 'Very good training program with clear explanations and engaging content. The instructor was knowledgeable and provided valuable insights throughout the session.',
            ],
            [
                'rating' => 5,
                'comments' => 'Outstanding training experience with well-structured modules and interactive learning approach. The hands-on activities greatly enhanced my understanding of the subject matter.',
            ],
            [
                'rating' => 3,
                'comments' => 'Good training overall but could benefit from more practical examples and case studies. The theoretical content was solid but needed more real-world applications.',
            ],
            [
                'rating' => 4,
                'comments' => 'Informative and well-organized training session. The materials provided were comprehensive and the pace was appropriate for learning complex topics effectively.',
            ],
            [
                'rating' => 5,
                'comments' => 'Exceptional training quality with expert instruction and relevant content. The interactive discussions and group activities made the learning experience highly engaging and productive.',
            ],
            [
                'rating' => 4,
                'comments' => 'Solid training program with good coverage of essential topics. The instructor demonstrated expertise and provided helpful feedback on assignments and practical exercises.',
            ],
            [
                'rating' => 2,
                'comments' => 'Training content was basic and lacked depth in certain areas. More advanced topics and challenging exercises would have made the session more valuable.',
            ],
            [
                'rating' => 5,
                'comments' => 'Highly effective training with clear learning objectives and measurable outcomes. The combination of theory and practice provided excellent foundation for skill development.',
            ],
            [
                'rating' => 4,
                'comments' => 'Well-delivered training session with good use of multimedia resources and interactive tools. The content was relevant and applicable to current industry standards.',
            ],
            [
                'rating' => 3,
                'comments' => 'Decent training program but room for improvement in delivery methods. More engaging activities and updated materials would enhance the overall learning experience.',
            ],
            [
                'rating' => 5,
                'comments' => 'Superb training experience with comprehensive curriculum and excellent instructor support. The practical assignments helped reinforce theoretical concepts learned during sessions.',
            ],
            [
                'rating' => 4,
                'comments' => 'Effective training with good balance of theoretical knowledge and practical application. The group discussions fostered collaborative learning and knowledge sharing among participants.',
            ],
            [
                'rating' => 1,
                'comments' => 'Training fell short of expectations with outdated content and poor organization. The materials need significant updates to reflect current best practices.',
            ],
            [
                'rating' => 5,
                'comments' => 'Excellent training program with innovative teaching methods and comprehensive resource materials. The instructor provided personalized attention and constructive feedback throughout.',
            ],
            [
                'rating' => 4,
                'comments' => 'Quality training session with well-prepared content and professional delivery. The practical workshops provided valuable hands-on experience with real-world scenarios.',
            ],
            [
                'rating' => 3,
                'comments' => 'Average training experience with standard content delivery. While informative, the session could benefit from more interactive elements and participant engagement activities.',
            ],
            [
                'rating' => 5,
                'comments' => 'Outstanding training quality with expert-level instruction and cutting-edge content. The certification process was thorough and the skills acquired are immediately applicable.',
            ],
            [
                'rating' => 4,
                'comments' => 'Good training program with structured approach and clear learning milestones. The assessment methods were fair and provided accurate evaluation of knowledge gained.',
            ],
            [
                'rating' => 2,
                'comments' => 'Training lacked practical focus and relied too heavily on theoretical concepts. More hands-on exercises and real-world case studies would improve effectiveness.',
            ],
            [
                'rating' => 5,
                'comments' => 'Exceptional training delivery with comprehensive coverage and excellent support materials. The follow-up resources and continued learning opportunities were particularly valuable.',
            ],
            [
                'rating' => 4,
                'comments' => 'Well-structured training with good progression from basic to advanced topics. The instructor maintained good pace and ensured all participants could follow along.',
            ],
            [
                'rating' => 3,
                'comments' => 'Satisfactory training session with adequate content coverage. However, more time allocation for practice sessions would have enhanced the overall learning outcome.',
            ],
            [
                'rating' => 5,
                'comments' => 'Brilliant training experience with innovative approaches and comprehensive skill development. The practical projects provided excellent opportunity to apply learned concepts immediately.',
            ],
            [
                'rating' => 4,
                'comments' => 'Effective training program with good use of technology and interactive learning tools. The content was up-to-date and relevant to current industry requirements.',
            ],
            [
                'rating' => 1,
                'comments' => 'Poor training quality with disorganized content and inadequate preparation. The session failed to meet stated learning objectives and needs significant improvement.',
            ],
            [
                'rating' => 5,
                'comments' => 'Top-notch training with world-class instruction and comprehensive curriculum design. The certification earned will significantly enhance professional credentials and career prospects.',
            ],
            [
                'rating' => 4,
                'comments' => 'Valuable training experience with practical insights and actionable knowledge. The networking opportunities with other participants added extra value to the program.',
            ],
            [
                'rating' => 3,
                'comments' => 'Reasonable training session with standard delivery and adequate materials. While educational, the program could benefit from more innovative teaching methodologies.',
            ],
            [
                'rating' => 5,
                'comments' => 'Phenomenal training quality with expert facilitators and state-of-the-art learning resources. The comprehensive approach ensured thorough understanding of all covered topics.',
            ],
        ];
        
        foreach ($tasks as $task) {
            // Only create feedback for completed tasks or tasks from completed/ongoing trainings
            if ($task->status === 'completed' || 
                ($task->training && in_array($task->training->status, ['completed', 'ongoing']))) {
                
                $feedbackCount = rand(1, 2); // 1-2 feedback per eligible task
                
                for ($i = 0; $i < $feedbackCount; $i++) {
                    $template = $feedbackTemplates[array_rand($feedbackTemplates)];
                    $taskDate = Carbon::parse($task->due_date);
                    
                    // Use assigned user if available, otherwise random user
                    $userId = $task->assigned_to && in_array($task->assigned_to, $userIds) 
                        ? $task->assigned_to 
                        : $userIds[array_rand($userIds)];
                    
                    TrainingFeedback::create([
                        'training_task_id' => $task->id,
                        'user_id' => $userId,
                        'rating' => $template['rating'],
                        'comments' => $template['comments'],
                        'creator_id' => 2,
                        'created_by' => 2,
                        'created_at' => $taskDate->copy()->addDays(rand(1, 7))->addHours(rand(1, 23))->addMinutes(rand(1, 59)),
                        'updated_at' => $taskDate->copy()->addDays(rand(1, 7))->addHours(rand(1, 23))->addMinutes(rand(1, 59)),
                    ]);
                }
            }
        }
    }
}