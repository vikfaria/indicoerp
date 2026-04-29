<?php

namespace Workdo\ZoomMeeting\Database\Seeders;

use Workdo\ZoomMeeting\Models\ZoomMeeting;
use Illuminate\Database\Seeder;
use App\Models\User;
use Carbon\Carbon;

class DemoZoomMeetingSeeder extends Seeder
{
    public function run($userId): void
    {
        if (empty($userId) || ZoomMeeting::where('created_by', $userId)->exists()) return;

        $users = User::where('created_by', $userId)->where('type', '!=', 'company')->get();
        if ($users->isEmpty()) return;

        $meetings = [
            'Weekly Team Standup Meeting - Sprint Planning & Progress Review' => 'Join us for our regular weekly team synchronization meeting where we will discuss current sprint progress, identify any blockers or challenges, review completed tasks, and plan upcoming priorities. This meeting ensures all team members are aligned on project goals and deliverables.',
            'Q4 Project Planning Session - Strategic Roadmap Development' => 'Comprehensive quarterly planning session focused on outlining project scope, defining clear timelines, allocating necessary resources, and establishing key milestones. We will review market requirements, assess technical feasibility, and create actionable development plans.',
            'Client Presentation Review - Final Pitch Preparation Meeting' => 'Critical review session to finalize all client presentation materials before the upcoming pitch meeting. We will rehearse key talking points, review slide content, practice demonstrations, and ensure all team members are prepared for client questions.',
            'Quarterly Business Review - Performance Analysis & Strategy' => 'Comprehensive quarterly review of business performance metrics, financial analysis, market positioning assessment, and strategic planning for the upcoming quarter. We will analyze KPIs, discuss growth opportunities, and adjust business strategies accordingly.',
            'Product Development Meeting - Feature Prioritization Workshop' => 'Strategic product development session focused on roadmap planning, feature prioritization based on user feedback, technical feasibility assessment, and development timeline coordination. We will review user stories, define acceptance criteria, and plan release schedules.',
            'Marketing Strategy Discussion - Campaign Planning & Execution' => 'Strategic marketing planning session to align upcoming campaigns with business objectives, review target audience analysis, discuss creative concepts, plan budget allocation, and coordinate cross-functional marketing initiatives for maximum impact.',
            'Sales Pipeline Review - Opportunity Assessment & Strategy' => 'Weekly comprehensive review of current sales pipeline, opportunity analysis, conversion rate assessment, lead qualification discussion, and strategic planning for closing deals. We will review prospect status and develop targeted approach strategies.',
            'Customer Feedback Analysis Session - Improvement Planning' => 'Detailed analysis of recent customer feedback, survey results, support ticket trends, and user experience data. We will identify improvement opportunities, prioritize enhancement initiatives, and develop action plans for customer satisfaction improvement.',
            'Annual Budget Planning Meeting - Resource Allocation Strategy' => 'Comprehensive annual budget planning session covering departmental resource allocation, capital expenditure planning, operational cost analysis, and strategic investment priorities. We will review financial projections and establish spending guidelines.',
            'Performance Review Discussion - Goal Setting & Development' => 'Individual performance review meeting focused on achievement assessment, goal setting for the upcoming quarter, professional development planning, skill gap analysis, and career progression discussion. We will establish clear objectives and success metrics.',
            'Technical Training Workshop - Skills Development Session' => 'Comprehensive training session covering new tools implementation, process improvements, best practices sharing, and technical skill development. We will provide hands-on learning opportunities and practical application exercises for team enhancement.',
            'Vendor Partnership Meeting - Contract Negotiation Session' => 'Strategic vendor negotiation meeting to discuss partnership terms, contract renewals, service level agreements, pricing negotiations, and long-term collaboration opportunities. We will review vendor performance and establish mutual expectations.',
            'Risk Assessment Review - Mitigation Strategy Development' => 'Comprehensive risk assessment meeting to identify potential project risks, business continuity threats, security vulnerabilities, and operational challenges. We will develop mitigation strategies and establish contingency plans for risk management.',
            'Quality Assurance Meeting - Process Improvement Review' => 'Quality assurance review session focused on product quality metrics, testing procedures evaluation, process improvement opportunities, and quality standards enhancement. We will discuss defect trends and implement quality improvement initiatives.',
            'Innovation Brainstorming Session - Creative Solution Workshop' => 'Creative innovation workshop designed to generate new ideas, explore emerging technologies, discuss market opportunities, and develop innovative solutions. We will encourage creative thinking and collaborative problem-solving for business growth.'
        ];

        $statuses = ['Scheduled', 'Started', 'Ended', 'Cancelled'];
        $durations = [30, 60, 90, 120];

        foreach ($meetings as $title => $description) {
            $participants = $users->random(rand(2, min(4, $users->count())))->pluck('id')->map(function($id) { return (string)$id; })->toArray();
            
            ZoomMeeting::create([
                'title' => $title,
                'description' => $description,
                'meeting_id' => rand(100, 999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'meeting_password' => substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6),
                'start_time' => Carbon::now()->addDays(rand(-180, 60))->setTime(rand(9, 17), [0, 30][rand(0, 1)]),
                'duration' => $durations[array_rand($durations)],
                'host_video' => (bool)rand(0, 1),
                'participant_video' => (bool)rand(0, 1),
                'waiting_room' => (bool)rand(0, 1),
                'recording' => rand(0, 3) === 0,
                'status' => $statuses[array_rand($statuses)],
                'participants' => $participants,
                'host_id' => (int)$participants[0],
                'creator_id' => $userId,
                'created_by' => $userId,
                'created_at' => Carbon::now()->subDays(rand(0, 180)),
            ]);
        }
    }
}