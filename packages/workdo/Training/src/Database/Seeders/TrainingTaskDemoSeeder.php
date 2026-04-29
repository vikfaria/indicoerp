<?php

namespace Workdo\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Workdo\Training\Models\TrainingTask;
use Workdo\Training\Models\Training;
use Carbon\Carbon;

class TrainingTaskDemoSeeder extends Seeder
{
    public function run($userId)
    {
        if (TrainingTask::where('created_by', $userId)->exists()) {
            return;
        }
        $trainings = Training::all();
        
        if ($trainings->isEmpty()) {
            return;
        }
        
        $taskTemplates = [
            [
                'title' => 'Pre-Training Assessment',
                'description' => 'Complete initial skills assessment and knowledge evaluation to establish baseline competency levels before training begins.',
                'days_offset' => -7,
                'status' => 'completed',
            ],
            [
                'title' => 'Review Training Materials',
                'description' => 'Study provided training materials, documentation, and resources to prepare for upcoming training sessions.',
                'days_offset' => -5,
                'status' => 'completed',
            ],
            [
                'title' => 'Attend Opening Session',
                'description' => 'Participate in training program introduction, meet instructors and fellow participants, understand program objectives.',
                'days_offset' => 0,
                'status' => 'completed',
            ],
            [
                'title' => 'Complete Module 1 Exercises',
                'description' => 'Work through practical exercises and assignments for the first training module to reinforce learning.',
                'days_offset' => 2,
                'status' => 'pending',
            ],
            [
                'title' => 'Group Discussion Participation',
                'description' => 'Actively participate in group discussions, share insights, and collaborate with other training participants.',
                'days_offset' => 3,
                'status' => 'pending',
            ],
            [
                'title' => 'Practical Workshop Session',
                'description' => 'Attend hands-on workshop session to apply theoretical knowledge in practical scenarios and real-world situations.',
                'days_offset' => 5,
                'status' => 'pending',
            ],
            [
                'title' => 'Mid-Training Progress Review',
                'description' => 'Meet with trainer for progress evaluation, discuss challenges, and receive feedback on performance.',
                'days_offset' => 7,
                'status' => 'pending',
            ],
            [
                'title' => 'Case Study Analysis',
                'description' => 'Analyze provided case studies, develop solutions, and present findings to demonstrate understanding.',
                'days_offset' => 10,
                'status' => 'pending',
            ],
            [
                'title' => 'Skill Demonstration',
                'description' => 'Demonstrate acquired skills through practical application and showcase competency in key areas.',
                'days_offset' => 12,
                'status' => 'pending',
            ],
            [
                'title' => 'Final Project Submission',
                'description' => 'Complete and submit final project incorporating all training concepts and demonstrating mastery.',
                'days_offset' => 15,
                'status' => 'pending',
            ],
            [
                'title' => 'Final Assessment',
                'description' => 'Take comprehensive final assessment to evaluate knowledge retention and skill acquisition.',
                'days_offset' => 17,
                'status' => 'pending',
            ],
            [
                'title' => 'Training Completion Certificate',
                'description' => 'Receive training completion certificate and participate in graduation ceremony.',
                'days_offset' => 20,
                'status' => 'pending',
            ],
        ];
        
        $users = \App\Models\User::emp()->where('created_by', 2)->select('id', 'name')->get();
        $userIds = $users->pluck('id')->toArray();
        if (empty($userIds)) {
            $userIds = [2];
        }
        
        foreach ($trainings as $training) {
            $trainingStartDate = Carbon::parse($training->start_date);
            $tasksPerTraining = rand(4, 8);
            
            for ($i = 0; $i < $tasksPerTraining; $i++) {
                $template = $taskTemplates[$i % count($taskTemplates)];
                $dueDate = $trainingStartDate->copy()->addDays($template['days_offset']);
                
                $status = $template['status'];
                if ($training->status === 'completed') {
                    $status = 'completed';
                } elseif ($training->status === 'cancelled') {
                    $status = 'pending';
                } elseif ($training->status === 'ongoing') {
                    if ($template['days_offset'] <= 5) {
                        $status = rand(0, 1) ? 'completed' : 'pending';
                    } else {
                        $status = 'pending';
                    }
                }
                
                TrainingTask::create([
                    'training_id' => $training->id,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'status' => $status,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'assigned_to' => $userIds[array_rand($userIds)],
                    'creator_id' => 2,
                    'created_by' => 2,
                    'created_at' => $trainingStartDate->copy()->subDays(rand(1, 10))->subHours(rand(1, 23))->subMinutes(rand(1, 59)),
                    'updated_at' => $trainingStartDate->copy()->addDays(rand(1, 5))->addHours(rand(1, 23))->addMinutes(rand(1, 59)),
                ]);
            }
        }
    }
}