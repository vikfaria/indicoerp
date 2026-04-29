<?php

namespace Workdo\Recruitment\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\CandidateAssessment;
use Workdo\Recruitment\Models\CandidateOnboarding;
use Workdo\Recruitment\Models\Interview;
use Workdo\Recruitment\Models\InterviewFeedback;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\RecruitmentSetting;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-recruitment-dashboard')) {
            $user = Auth::user();

            if ($user->type !== 'company' && $user->type !== 'hr') {
                return $this->staffDashboard();
            }

            $creatorId = creatorId();

            try {
                // Overview Statistics
                $totalCandidates = Candidate::where('created_by', $creatorId)->count();
            } catch (\Exception $e) {
                $totalCandidates = 0;
            }

            try {
                $completedOnboardings = CandidateOnboarding::where('created_by', $creatorId)->where('status', 'Completed')->count();
            } catch (\Exception $e) {
                $completedOnboardings = 0;
            }

            try {
                $activeJobPostings = JobPosting::where('created_by', $creatorId)->where('is_published', true)->count();
            } catch (\Exception $e) {
                $activeJobPostings = 0;
            }

            try {
                $pendingInterviews = Interview::where('created_by', $creatorId)->where('status', '0')->count();
            } catch (\Exception $e) {
                $pendingInterviews = 0;
            }

            // Calendar Events
            try {
                $isDemo = config('app.is_demo');
                if ($isDemo) {
                    $calendarEvents = collect([
                        ['id' => 101, 'title' => 'Interview: Alice Smith - PHP Developer', 'date' => Carbon::now()->subMonth()->day(10)->format('Y-m-d'), 'time' => '10:00 AM', 'status' => 'Completed'],
                        ['id' => 102, 'title' => 'Interview: Bob Johnson - UI/UX Designer', 'date' => Carbon::now()->day(5)->format('Y-m-d'), 'time' => '11:30 AM', 'status' => 'Scheduled'],
                        ['id' => 103, 'title' => 'Interview: Charlie Brown - QA Engineer', 'date' => Carbon::now()->day(15)->format('Y-m-d'), 'time' => '02:00 PM', 'status' => 'Scheduled'],
                        ['id' => 104, 'title' => 'Interview: Diana Prince - HR Manager', 'date' => Carbon::now()->addMonth()->day(8)->format('Y-m-d'), 'time' => '03:30 PM', 'status' => 'Scheduled'],
                        ['id' => 105, 'title' => 'Interview: Evan Wright - Project Manager', 'date' => Carbon::now()->addMonth()->day(20)->format('Y-m-d'), 'time' => '09:00 AM', 'status' => 'Scheduled'],
                    ]);
                } else {
                    $calendarEvents = Interview::where('created_by', $creatorId)
                        ->where('status', '0')
                        ->with(['candidate', 'jobPosting'])
                        ->get()
                        ->map(function ($interview) {
                            return [
                                'id' => $interview->id,
                                'title' => ($interview->candidate ? $interview->candidate->first_name . ' ' . $interview->candidate->last_name : 'Interview') .
                                          ($interview->jobPosting ? ' - ' . $interview->jobPosting->title : ''),
                                'date' => $interview->scheduled_date,
                                'time' => $interview->scheduled_time ?? '09:00',
                                'status' => $interview->status ?? 'Scheduled'
                            ];
                        });
                }
            } catch (\Exception $e) {
                $calendarEvents = collect([]);
            }

            // Candidates by Status
            try {
                $candidatesByStatus = [
                    'applied' => Candidate::where('created_by', $creatorId)->where('status', '0')->count(), // New
                    'shortlisted' => Candidate::where('created_by', $creatorId)->where('status', '1')->count(), // Screening
                    'interviewScheduled' => Candidate::where('created_by', $creatorId)->where('status', '2')->count(), // Interview
                    'hired' => Candidate::where('created_by', $creatorId)->where('status', '4')->count(), // Hired
                    'rejected' => Candidate::where('created_by', $creatorId)->where('status', '5')->count(), // Rejected
                ];
            } catch (\Exception $e) {
                $candidatesByStatus = [
                    'applied' => 0,
                    'shortlisted' => 0,
                    'interviewScheduled' => 0,
                    'hired' => 0,
                    'rejected' => 0,
                ];
            }
            // Onboarding Status
            try {
                $onboardingStatus = [
                    'pending' => CandidateOnboarding::where('created_by', $creatorId)->where('status', 'Pending')->count(),
                    'inProgress' => CandidateOnboarding::where('created_by', $creatorId)->where('status', 'In Progress')->count(),
                    'completed' => CandidateOnboarding::where('created_by', $creatorId)->where('status', 'Completed')->count(),
                ];
            } catch (\Exception $e) {
                $onboardingStatus = [
                    'pending' => 0,
                    'inProgress' => 0,
                    'completed' => 0,
                ];
            }

            // Recent Activities
            try {
                $latestCandidates = Candidate::where('created_by', $creatorId)
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function ($candidate) {
                        return [
                            'name' => $candidate->first_name . ' ' . $candidate->last_name,
                            'position' => 'N/A'
                        ];
                    });
            } catch (\Exception $e) {
                $latestCandidates = collect([]);
            }

            // Hiring Funnel Data
            try {
                $hiringFunnel = [
                    'applications' => $candidatesByStatus['applied'] + $candidatesByStatus['shortlisted'] + $candidatesByStatus['interviewScheduled'] + $candidatesByStatus['hired'] + $candidatesByStatus['rejected'],
                    'shortlisted' => $candidatesByStatus['shortlisted'],
                    'interviewed' => $candidatesByStatus['interviewScheduled'],
                    'hired' => $candidatesByStatus['hired']
                ];
            } catch (\Exception $e) {
                $hiringFunnel = [
                    'applications' => 0,
                    'shortlisted' => 0,
                    'interviewed' => 0,
                    'hired' => 0
                ];
            }

            $dashboardData = [
                'overview' => [
                    'totalCandidates' => $totalCandidates,
                    'activeJobPostings' => $activeJobPostings,
                    'pendingInterviews' => $pendingInterviews,
                    'completedOnboardings' => $completedOnboardings,
                ],
                'candidatesByStatus' => $candidatesByStatus,
                'interviewStatus' => [
                    'pending' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                ],
                'onboardingStatus' => $onboardingStatus,
                'hiringFunnel' => $hiringFunnel,
                'calendarEvents' => $calendarEvents,
                'recentActivities' => [
                    'latestCandidates' => $latestCandidates,
                    'upcomingInterviews' => collect([]),
                ],
                'jobPostings' => collect([]),
                'alerts' => [
                    'overdueInterviews' => 0,
                    'pendingReviews' => 0,
                    'incompleteOnboardings' => 0,
                    'expiringJobs' => 0,
                ],
            ];

            return Inertia::render('Recruitment/Index', [
                'dashboardData' => $dashboardData,
                'userSlug' => Auth::user()->slug,
                'welcomeCard' => $this->getWelcomeCardSettings(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function getWelcomeCardSettings()
    {
        try {
            $settings = RecruitmentSetting::where('created_by', creatorId())
                ->where('key', 'dashboard_welcome_card')
                ->first();

            return $settings ? json_decode($settings->value, true) : [
                'card_title' => __('Recruitment Hub'),
                'card_description' => __('Manage your talent pipeline efficiently. Streamline hiring process from job posting to onboarding.'),
                'button_text' => __('Copy Portal Link'),
                'button_icon' => __('Copy')
            ];
        } catch (\Exception $e) {
            return [
                'card_title' => __('Recruitment Hub'),
                'card_description' => __('Manage your talent pipeline efficiently. Streamline hiring process from job posting to onboarding.'),
                'button_text' => __('Copy Portal Link'),
                'button_icon' => __('Copy')
            ];
        }
    }

    private function staffDashboard()
    {
        $user = Auth::user();
        $userId = $user->id;

        try {
            // Get assigned interviews (where user is interviewer)
            $assignedInterviews = Interview::where('created_by', creatorId())
                ->whereJsonContains('interviewer_ids', (string) $userId)
                ->pluck('id');

            // Get created interviews
            $createdInterviews = Interview::where('creator_id', $userId)->pluck('id');

            // Combine both
            $allInterviewIds = $assignedInterviews->merge($createdInterviews)->unique();

            // Get candidate onboardings where user is buddy or creator
            $assignedOnboardings = CandidateOnboarding::where('created_by', creatorId())
                ->where(function($q) use ($userId) {
                    $q->where('creator_id', $userId)
                      ->orWhere('buddy_employee_id', $userId);
                })
                ->pluck('id');

            // Get candidate assessments conducted by user
            $conductedAssessments = CandidateAssessment::where('created_by', creatorId())
                ->where('conducted_by', $userId)
                ->pluck('id');

            // Get interview feedbacks created by user or where user is interviewer
            $userFeedbacks = InterviewFeedback::where('created_by', creatorId())
                ->where(function($q) use ($userId) {
                    $q->where('creator_id', $userId)
                      ->orWhereJsonContains('interviewer_ids', (string) $userId);
                })
                ->pluck('id');
        } catch (\Exception $e) {
            $allInterviewIds = collect([]);
            $assignedOnboardings = collect([]);
            $conductedAssessments = collect([]);
            $userFeedbacks = collect([]);
        }

        // Overview Statistics
        try {
            $assignedInterviewsCount = $allInterviewIds->count();
            $pendingInterviews = Interview::whereIn('id', $allInterviewIds)->where('status', '0')->count();
            $completedInterviews = Interview::whereIn('id', $allInterviewIds)->where('status', '1')->count();
            $assignedOnboardingsCount = $assignedOnboardings->count();
            $completedOnboardings = CandidateOnboarding::whereIn('id', $assignedOnboardings)->where('status', 'Completed')->count();
            $conductedAssessmentsCount = $conductedAssessments->count();
            $submittedFeedbacks = $userFeedbacks->count();
        } catch (\Exception $e) {
            $assignedInterviewsCount = 0;
            $pendingInterviews = 0;
            $completedInterviews = 0;
            $assignedOnboardingsCount = 0;
            $completedOnboardings = 0;
            $conductedAssessmentsCount = 0;
            $submittedFeedbacks = 0;
        }

        // Calendar Events from assigned interviews
        try {
            $isDemo = config('app.is_demo');
            if ($isDemo) {
                $calendarEvents = collect([
                    ['id' => 101, 'title' => 'Interview: Alice Smith - PHP Developer', 'date' => Carbon::now()->subMonth()->day(10)->format('Y-m-d'), 'time' => '10:00 AM', 'status' => 'Completed'],
                    ['id' => 102, 'title' => 'Interview: Bob Johnson - UI/UX Designer', 'date' => Carbon::now()->day(5)->format('Y-m-d'), 'time' => '11:30 AM', 'status' => 'Scheduled'],
                    ['id' => 103, 'title' => 'Interview: Charlie Brown - QA Engineer', 'date' => Carbon::now()->day(15)->format('Y-m-d'), 'time' => '02:00 PM', 'status' => 'Scheduled'],
                    ['id' => 104, 'title' => 'Interview: Diana Prince - HR Manager', 'date' => Carbon::now()->addMonth()->day(8)->format('Y-m-d'), 'time' => '03:30 PM', 'status' => 'Scheduled'],
                    ['id' => 105, 'title' => 'Interview: Evan Wright - Project Manager', 'date' => Carbon::now()->addMonth()->day(20)->format('Y-m-d'), 'time' => '09:00 AM', 'status' => 'Scheduled'],
                ]);
            } else {
                $calendarEvents = Interview::whereIn('id', $allInterviewIds)
                    ->where('status', '0')
                    ->with(['candidate', 'jobPosting'])
                    ->get()
                    ->map(function ($interview) {
                        return [
                            'id' => $interview->id,
                            'title' => ($interview->candidate ? $interview->candidate->first_name . ' ' . $interview->candidate->last_name : 'Interview') .
                                      ($interview->jobPosting ? ' - ' . $interview->jobPosting->title : ''),
                            'date' => $interview->scheduled_date,
                            'time' => $interview->scheduled_time ?? '09:00',
                            'status' => $interview->status ?? 'Scheduled'
                        ];
                    });
            }
        } catch (\Exception $e) {
            $calendarEvents = collect([]);
        }

        // Recent assigned interviews
        try {
            $recentInterviews = Interview::whereIn('id', $allInterviewIds)
                ->with(['candidate', 'jobPosting'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($interview) {
                    return [
                        'id' => $interview->id,
                        'candidate_name' => $interview->candidate ? $interview->candidate->first_name . ' ' . $interview->candidate->last_name : 'Unknown',
                        'job_title' => $interview->jobPosting ? $interview->jobPosting->title : 'Unknown Job',
                        'scheduled_date' => $interview->scheduled_date,
                        'status' => $interview->status
                    ];
                });
        } catch (\Exception $e) {
            $recentInterviews = collect([]);
        }

        // Recent assigned onboardings
        try {
            $recentOnboardings = CandidateOnboarding::whereIn('id', $assignedOnboardings)
                ->with(['candidate', 'checklist'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($onboarding) {
                    return [
                        'id' => $onboarding->id,
                        'candidate_name' => $onboarding->candidate ? $onboarding->candidate->first_name . ' ' . $onboarding->candidate->last_name : 'Unknown',
                        'checklist_name' => $onboarding->checklist ? $onboarding->checklist->name : 'Unknown Checklist',
                        'start_date' => $onboarding->start_date,
                        'status' => $onboarding->status
                    ];
                });
        } catch (\Exception $e) {
            $recentOnboardings = collect([]);
        }

        // Task status for staff
        try {
            $taskStatus = [
                'pendingInterviews' => $pendingInterviews,
                'completedInterviews' => $completedInterviews,
                'pendingOnboardings' => CandidateOnboarding::whereIn('id', $assignedOnboardings)->where('status', 'Pending')->count(),
                'completedOnboardings' => $completedOnboardings
            ];
        } catch (\Exception $e) {
            $taskStatus = [
                'pendingInterviews' => 0,
                'completedInterviews' => 0,
                'pendingOnboardings' => 0,
                'completedOnboardings' => 0
            ];
        }

        $dashboardData = [
            'overview' => [
                'assignedInterviews' => $assignedInterviewsCount,
                'pendingInterviews' => $pendingInterviews,
                'assignedOnboardings' => $assignedOnboardingsCount,
                'completedOnboardings' => $completedOnboardings,
                'conductedAssessments' => $conductedAssessmentsCount,
                'submittedFeedbacks' => $submittedFeedbacks,
            ],
            'taskStatus' => $taskStatus,
            'calendarEvents' => $calendarEvents,
            'recentActivities' => [
                'recentInterviews' => $recentInterviews,
                'recentOnboardings' => $recentOnboardings,
            ],
            'alerts' => [
                'overdueInterviews' => 0,
                'pendingFeedbacks' => Interview::whereIn('id', $allInterviewIds)->where('feedback_submitted', 0)->count(),
                'upcomingInterviews' => Interview::whereIn('id', $allInterviewIds)->where('status', '0')->whereDate('scheduled_date', '>=', Carbon::today())->count(),
            ],
        ];

        return Inertia::render('Recruitment/Dashboard/StaffDashboard', [
            'dashboardData' => $dashboardData,
            'userSlug' => Auth::user()->slug
        ]);
    }
}
