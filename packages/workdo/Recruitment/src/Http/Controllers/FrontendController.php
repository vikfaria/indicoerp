<?php

namespace Workdo\Recruitment\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\RecruitmentSetting;
use Workdo\Recruitment\Models\JobType;
use Workdo\Recruitment\Models\JobLocation;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\CandidateSources;
use App\Models\User;
use Carbon\Carbon;
use Workdo\Recruitment\Models\CustomQuestion;
use Workdo\Recruitment\Models\Offer;
use Workdo\Recruitment\Http\Requests\SubmitApplicationRequest;
use Workdo\Recruitment\Events\SubmitApplication;

class FrontendController extends Controller
{
    private function getUserIdFromRequest(Request $request)
    {
        $userSlug = $request->route('userSlug');
        if ($userSlug) {
            $user = User::where('slug', $userSlug)->first();
            if ($user) {
                return $user->id;
            }
        }
        abort(404, __('Recruitment page not found'));
    }

    public function jobListings(Request $request, $userSlug)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);

            $jobs = JobPosting::where('is_published', true)
                ->where('status', 'active')
                ->where('created_by', $userId)
                ->with(['jobType', 'location'])
                ->latest()
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'encrypted_id' => $job->encrypted_id,
                        'title' => $job->title,
                        'location' => $job->location ? $job->location->name : 'Not specified',
                        'jobType' => $job->jobType ? $job->jobType->name : 'Full Time',
                        'salaryFrom' => $job->min_salary ?? 0,
                        'salaryTo' => $job->max_salary ?? 0,
                        'postedDate' => $job->publish_date ? Carbon::parse($job->publish_date)->format('Y-m-d') : $job->created_at->format('Y-m-d'),
                        'deadlineDate' => $job->application_deadline ? Carbon::parse($job->application_deadline)->format('Y-m-d') : null,
                        'skills' => $job->skills ? explode(',', trim($job->skills)) : [],
                        'featured' => $job->is_featured ?? false,
                        'description' => $job->description ?? '',
                        'job_application' => $job->job_application ?? 'existing',
                        'application_url' => $job->application_url
                    ];
                });

            // Get job categories (departments)
            $jobCategories = ['Technology', 'Accounting', 'HR section'];

            // Get job locations
            $jobLocations = JobLocation::where('created_by', $userId)
                ->where('status', 1)
                ->select('id', 'name')
                ->get()
                ->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name
                    ];
                });

            // Get job types
            $jobTypes = JobType::where('created_by', $userId)
                ->where('is_active', 1)
                ->select('id', 'name')
                ->get()
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'name' => $type->name
                    ];
                });

            return Inertia::render('Recruitment/Frontend/JobListings', [
                'jobs' => $jobs,
                'jobCategories' => $jobCategories,
                'jobLocations' => $jobLocations,
                'jobTypes' => $jobTypes,
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    public function jobDetails(Request $request, $userSlug, $id)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $job = JobPosting::findByEncryptedId($id);

            if (!$job || !$job->is_published || $job->created_by != $userId) {
                return Inertia::render('Recruitment/Frontend/NotFound');
            }

            $job->load(['jobType', 'location']);
            $jobData = [
                'id' => $job->id,
                'encrypted_id' => $job->encrypted_id,
                'title' => $job->title,
                'location' => $job->location ? $job->location->name : 'Not specified',
                'jobType' => $job->jobType ? $job->jobType->name : 'Full Time',
                'salaryFrom' => $job->min_salary ?? 0,
                'salaryTo' => $job->max_salary ?? 0,
                'positions' => $job->position ?? 0,
                'startDate' => $job->start_date ?? now()->format('Y-m-d'),
                'endDate' => $job->application_deadline ? Carbon::parse($job->application_deadline)->format('Y-m-d') : now()->addMonth()->format('Y-m-d'),
                'postedDate' => $job->publish_date ? Carbon::parse($job->publish_date)->format('Y-m-d') : $job->created_at->format('Y-m-d'),
                'skills' => $job->skills ? explode(',', trim($job->skills)) : [],
                'featured' => (bool)($job->is_featured ?? false),
                'description' => $job->description ?? '',
                'requirements' => $job->requirements ?? '',
                'benefits' => $job->benefits ?? '',
                'job_application' => $job->job_application ?? 'existing',
                'application_url' => $job->application_url
            ];

            $companySettings = RecruitmentSetting::where('created_by', $userId)
                ->whereIn('key', ['our_mission', 'company_size', 'industry'])
                ->pluck('value', 'key');

            return Inertia::render('Recruitment/Frontend/JobDetails', [
                'job' => $jobData,
                'companyInfo' => [
                    'ourMission' => $companySettings['our_mission'] ?? '',
                    'companySize' => $companySettings['company_size'] ?? '',
                    'industry' => $companySettings['industry'] ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    public function jobApply(Request $request, $userSlug, $id)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $job = JobPosting::findByEncryptedId($id);

            if (!$job || !$job->is_published || $job->created_by != $userId) {
                return Inertia::render('Recruitment/Frontend/NotFound');
            }

            $job->load(['jobType', 'location']);

            $jobData = [
                'id' => $job->id,
                'encrypted_id' => $job->encrypted_id,
                'title' => $job->title,
                'location' => $job->location ? $job->location->name : 'Not specified',
                'jobType' => $job->jobType ? $job->jobType->name : 'Full Time',
                'salaryFrom' => $job->min_salary ?? 0,
                'salaryTo' => $job->max_salary ?? 0,
                'skills' => $job->skills ? explode(',', trim($job->skills)) : [],
                'featured' => (bool)($job->is_featured ?? false),
                'terms_condition' => $job->terms_condition,
                'show_terms_condition' => (bool)($job->show_terms_condition ?? false)
            ];

            $settings = RecruitmentSetting::where('created_by', $userId)->pluck('value', 'key');
            $applicationTips = $settings['application_tips'] ?? null;
            $tips = $applicationTips ? json_decode($applicationTips, true) : [];

            $customQuestions = [];
            if ($job->custom_questions) {
                $questionIds = is_string($job->custom_questions) ? json_decode($job->custom_questions, true) : $job->custom_questions;
                if (is_array($questionIds) && !empty($questionIds)) {
                    $customQuestions = CustomQuestion::whereIn('id', $questionIds)
                        ->where('is_active', true)
                        ->where('created_by', $userId)
                        ->orderBy('sort_order')
                        ->get()
                        ->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question' => $question->question,
                                'type' => $question->type,
                                'options' => $question->options ? json_decode($question->options, true) : [],
                                'is_required' => $question->is_required
                            ];
                        });
                }
            }

            return Inertia::render('Recruitment/Frontend/JobApply', [
                'job' => $jobData,
                'applicationTips' => $tips,
                'storageSettings' => [
                    'allowedFileTypes' => strtoupper(str_replace(',', ', ', admin_setting('allowedFileTypes') ?? 'PDF, DOC, DOCX')),
                    'maxUploadSize' => (int) (admin_setting('maxUploadSize') ?? 10)
                ],
                'customQuestions' => $customQuestions,
                'jobPostingSettings' => [
                    'applicant' => $job->applicant ?? [],
                    'visibility' => $job->visibility ?? []
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    public function applicationSuccess(Request $request, $userSlug, $trackingId = null)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $settings = RecruitmentSetting::where('created_by', $userId)->pluck('value', 'key');

            $candidate = null;
            $errorMessage = null;

            if ($trackingId) {
                $candidate = Candidate::where('tracking_id', $trackingId)
                    ->where('created_by', $userId)
                    ->with('job_posting')
                    ->first();

                if (!$candidate) {
                    $cleanTrackingId = preg_match('/^(TRK[A-Z0-9]+)/', $trackingId, $matches) ? $matches[1] : null;

                    if ($cleanTrackingId && $cleanTrackingId !== $trackingId) {
                        return redirect()->route('recruitment.frontend.careers.application.success', [
                            'userSlug' => $userSlug,
                            'trackingId' => $cleanTrackingId
                        ])->with('error', __('Invalid tracking ID format. Please check your tracking ID.'));
                    }

                    $errorMessage = __('Application not found. Please check your tracking ID.');
                }
            }

            $applicationData = [
                'trackingId' => $candidate ? $candidate->tracking_id : 'TRK-DEMO-001',
                'jobTitle' => $candidate && $candidate->job_posting ? $candidate->job_posting->title : 'Demo Position',
                'appliedDate' => $candidate ? $candidate->application_date->format('M d, Y') : now()->format('M d, Y'),
                'applicantName' => $candidate ? $candidate->first_name . ' ' . $candidate->last_name : 'Demo User',
                'email' => $candidate ? $candidate->email : 'demo@example.com'
            ];

            $whatHappensNextData = $settings['what_happens_next'] ?? null;
            $whatHappensNext = $whatHappensNextData ? json_decode($whatHappensNextData, true) : [];

            $needHelp = null;
            if (isset($settings['need_help_description']) && isset($settings['need_help_email']) && isset($settings['need_help_phone'])) {
                $needHelp = [
                    'title' => 'Need Help?',
                    'description' => $settings['need_help_description'],
                    'email' => $settings['need_help_email'],
                    'phone' => $settings['need_help_phone']
                ];
            }

            return Inertia::render('Recruitment/Frontend/Success', [
                'applicationData' => $applicationData,
                'errorMessage' => $errorMessage,
                'whatHappensNext' => $whatHappensNext,
                'needHelp' => $needHelp
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    public function trackingForm(Request $request, $userSlug)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $settings = RecruitmentSetting::where('created_by', $userId)->pluck('value', 'key');

            $trackingFaqData = $settings['tracking_faq'] ?? null;
            $trackingFaq = $trackingFaqData ? json_decode($trackingFaqData, true) : null;

            return Inertia::render('Recruitment/Frontend/TrackingForm', [
                'trackingFaq' => $trackingFaq
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    public function trackingVerify(Request $request, $userSlug)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);

            $request->validate([
                'tracking_id' => 'required|string',
                'email' => 'required|email'
            ]);

            // Find candidate with matching tracking ID and email
            $candidate = Candidate::where('tracking_id', $request->tracking_id)
                ->where('email', $request->email)
                ->where('created_by', $userId)
                ->first();

            if (!$candidate) {
                return back()->withErrors([
                    'message' => __('No application found with the provided tracking ID and email address. Please check your details and try again.')
                ]);
            }

            // Redirect to tracking details page
            return redirect()->route('recruitment.frontend.careers.track.details', [
                'userSlug' => $userSlug,
                'trackingId' => $candidate->tracking_id
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['message' => __('An error occurred. Please try again.')]);
        }
    }

    public function trackingDetails(Request $request, $userSlug, $trackingId)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $settings = RecruitmentSetting::where('created_by', $userId)->pluck('value', 'key');

            $candidate = Candidate::where('tracking_id', $trackingId)
                ->where('created_by', $userId)
                ->with([
                    'job_posting.jobType',
                    'job_posting.location',
                    'interviews.interviewRound',
                    'interviews.interviewType'
                ])
                ->first();

            $jobDetails = null;
            $candidateDetails = null;
            $applicationStatus = null;

            if ($candidate && $candidate->job_posting) {
                $job = $candidate->job_posting;
                $jobDetails = [
                    'title' => $job->title,
                    'location' => $job->location ? $job->location->name : 'Not specified',
                    'jobType' => $job->jobType ? $job->jobType->name : 'Full Time',
                    'salaryFrom' => $job->min_salary ?? 0,
                    'salaryTo' => $job->max_salary ?? 0,
                    'department' => is_object($job->department) ? $job->department->department_name : ($job->department ?? 'General')
                ];
            }

            if ($candidate) {
                $candidateDetails = [
                    'fullName' => $candidate->first_name . ' ' . $candidate->last_name,
                    'email' => $candidate->email,
                    'phone' => $candidate->phone,
                    'appliedDate' => $candidate->application_date
                ];

                $interviewDetails = $this->getInterviewDetails($candidate);
                $offerDetails = $this->getOfferDetails($candidate);

                $currentStatus = (int) $candidate->status;
                $applicationStatus = [
                    'currentStatus' => $currentStatus,
                    'timeline' => $this->generateTimeline($candidate, $currentStatus, $interviewDetails, $offerDetails)
                ];
            }

            $needHelp = null;
            if (isset($settings['need_help_description']) && isset($settings['need_help_email']) && isset($settings['need_help_phone'])) {
                $needHelp = [
                    'title' => __('Need Help?'),
                    'description' => $settings['need_help_description'],
                    'email' => $settings['need_help_email'],
                    'phone' => $settings['need_help_phone']
                ];
            }

            return Inertia::render('Recruitment/Frontend/TrackingDetails', [
                'trackingId' => $trackingId,
                'jobDetails' => $jobDetails,
                'candidateDetails' => $candidateDetails,
                'applicationStatus' => $applicationStatus,
                'interviewDetails' => $interviewDetails,
                'offerDetails' => $offerDetails,
                'needHelp' => $needHelp
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    private function generateTimeline($candidate, $currentStatus, $interviewDetails = null, $offerDetails = null)
    {
        $timeline = [
            [
                'status' => 0,
                'title' => __('Application Submitted'),
                'description' => __('Your application has been successfully submitted'),
                'date' => $candidate->application_date->format('Y-m-d'),
                'completed' => true
            ]
        ];

        if ($currentStatus >= 1) {
            $timeline[] = [
                'status' => 1,
                'title' => __('Screening'),
                'description' => __('Your application is being screened by our team'),
                'date' => $candidate->updated_at->format('Y-m-d'),
                'completed' => $currentStatus > 1
            ];
        }

        if ($currentStatus >= 2) {
            $description = __('Interview process is in progress');
            $date = $candidate->updated_at->format('Y-m-d');

            if ($interviewDetails) {
                $description = __('Interview scheduled for ' . $interviewDetails['date'] . ' at ' . $interviewDetails['time']);
                $date = $interviewDetails['date'];
            }

            $timeline[] = [
                'status' => 2,
                'title' => __('Interview'),
                'description' => $description,
                'date' => $date,
                'completed' => $currentStatus > 2,
                'details' => $interviewDetails
            ];
        }

        if ($currentStatus >= 3) {
            $description = __('Job offer has been extended');
            $date = $candidate->updated_at->format('Y-m-d');

            if ($offerDetails) {
                $description = __('Job offer extended - expires on ' . $offerDetails['expiration_date']);
                $date = $offerDetails['offer_date'];
            }

            $timeline[] = [
                'status' => 3,
                'title' => __('Offer'),
                'description' => $description,
                'date' => $date,
                'completed' => $currentStatus > 3,
                'details' => $offerDetails
            ];
        }

        if ($currentStatus == 5) {
            $timeline[] = [
                'status' => 5,
                'title' => __('Rejected'),
                'description' => __('Application was not successful this time'),
                'date' => $candidate->updated_at->format('Y-m-d'),
                'completed' => true
            ];
        } elseif ($currentStatus >= 4) {
            $timeline[] = [
                'status' => 4,
                'title' => __('Hired'),
                'description' => __('Congratulations! You have been hired'),
                'date' => $candidate->updated_at->format('Y-m-d'),
                'completed' => true
            ];
        } else {
            $timeline[] = [
                'status' => 4,
                'title' => __('Final Decision'),
                'description' => __('We will notify you of our final decision'),
                'date' => null,
                'completed' => false
            ];
        }

        return $timeline;
    }

    private function getInterviewDetails($candidate)
    {
        $interview = $candidate->interviews()->latest()->first();

        if (!$interview) {
            return null;
        }

        return [
            'id' => $interview->id,
            'date' => $interview->scheduled_date,
            'time' => $interview->scheduled_time,
            'duration' => $interview->duration,
            'location' => $interview->location,
            'meeting_link' => $interview->meeting_link,
            'round' => $interview->interviewRound ? $interview->interviewRound->name : 'Interview',
            'type' => $interview->interviewType ? $interview->interviewType->name : 'General',
            'status' => $interview->status
        ];
    }

    private function getOfferDetails($candidate)
    {
        $offer = Offer::where('candidate_id', $candidate->id)->latest()->first();

        if (!$offer) {
            return null;
        }

        return [
            'id' => $offer->id,
            'offer_date' => $offer->offer_date,
            'position' => $offer->position,
            'salary' => $offer->salary,
            'bonus' => $offer->bonus,
            'start_date' => $offer->start_date,
            'expiration_date' => $offer->expiration_date,
            'status' => $offer->status,
            'benefits' => $offer->benefits
        ];
    }

    public function jobTerms(Request $request, $userSlug, $id)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $job = JobPosting::findByEncryptedId($id);

            if (!$job || !$job->is_published || $job->created_by != $userId) {
                return Inertia::render('Recruitment/Frontend/NotFound');
            }

            return Inertia::render('Recruitment/Frontend/JobTerms', [
                'job' => [
                    'id' => $job->id,
                    'encrypted_id' => $job->encrypted_id,
                    'title' => $job->title,
                    'terms_condition' => $job->terms_condition
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Recruitment/Frontend/NotFound');
        }
    }

    public function offerResponse(Request $request, $userSlug, $offerId)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);

            $request->validate([
                'status' => 'required|in:2,4' // 2=accepted, 4=declined
            ]);

            $offer = Offer::where('id', $offerId)
                ->where('created_by', $userId)
                ->first();

            if (!$offer) {
                return back()->withErrors(['message' => __('Offer not found')]);
            }

            $offer->status = $request->status;
            $offer->response_date = now();
            $offer->save();

            $message = $request->status === '2' ? __('Offer accepted successfully!') : __('Offer declined successfully.');

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['message' => __('An error occurred. Please try again.')]);
        }
    }

    public function submitApplication(SubmitApplicationRequest $request, $userSlug, $id)
    {
        try {
            $userId = $this->getUserIdFromRequest($request);
            $job = JobPosting::findByEncryptedId($id);

            if (!$job || !$job->is_published || $job->created_by != $userId) {
                return back()->withErrors(['error' => __('Job not found or no longer available.')]);
            }

            // Parse name field
            $nameParts = explode(' ', trim($request->name), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Validate request
            $request->validated();

            // Get or create default candidate source
            $candidateSource = CandidateSources::where('created_by', $userId)
                ->where('is_active', 1)
                ->where('name', 'Career Portal')
                ->first();

            if (!$candidateSource) {
                $candidateSource = CandidateSources::create([
                    'name' => __('Career Portal'),
                    'description' => __('Applications from career portal'),
                    'is_active' => true,
                    'created_by' => $userId,
                    'creator_id' => $userId
                ]);
            }

            // Handle file uploads using upload_file helper
            $resumePath = null;
            $coverLetterPath = null;
            $profilePhotoPath = null;

            if ($request->hasFile('profilePhoto')) {
                $filenameWithExt = $request->file('profilePhoto')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('profilePhoto')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'profilePhoto', $fileNameToStore, 'candidates/profiles');
                if ($upload['flag'] == 1) {
                    $profilePhotoPath = $upload['url']; // Store only filename
                }
            }

            if ($request->hasFile('resume')) {
                $filenameWithExt = $request->file('resume')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('resume')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'resume', $fileNameToStore, 'candidates/resumes');
                if ($upload['flag'] == 1) {
                    $resumePath = $upload['url'];
                }
            }

            if ($request->hasFile('coverLetter')) {
                $filenameWithExt = $request->file('coverLetter')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('coverLetter')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'coverLetter', $fileNameToStore, 'candidates/cover_letters');
                if ($upload['flag'] == 1) {
                    $coverLetterPath = $upload['url'];
                }
            }

            // Collect custom question responses
            $customQuestionResponses = [];
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'custom_question_')) {
                    $customQuestionResponses[$key] = $value;
                }
            }

            // Generate unique tracking ID
            $trackingId = Candidate::generateTrackingId($userId);

            // Create candidate record
            $candidate = Candidate::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->email,
                'phone' => $request->phone,
                'dob' => $request->dateOfBirth,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'gender' => $request->gender,
                'current_company' => $request->currentCompany,
                'current_position' => $request->currentPosition,
                'experience_years' => $request->experienceYears,
                'current_salary' => $request->currentSalary,
                'expected_salary' => $request->expectedSalary,
                'notice_period' => $request->noticePeriod,
                'skills' => $request->skills,
                'education' => $request->education,
                'portfolio_url' => $request->portfolioUrl,
                'linkedin_url' => $request->linkedinUrl,
                'profile_path' => $profilePhotoPath,
                'resume_path' => $resumePath,
                'cover_letter_path' => $coverLetterPath,
                'custom_question' => !empty($customQuestionResponses) ? json_encode($customQuestionResponses) : null,
                'tracking_id' => $trackingId,
                'status' => 0,
                'application_date' => now(),
                'job_id' => $job->id,
                'source_id' => $candidateSource->id,
                'created_by' => $userId,
                'creator_id' => $userId
            ]);

            SubmitApplication::dispatch($request, $candidate);

            // Send Application Received email
            if (company_setting('email_fromAddress', $userId)) {
                $trackingLink = route('recruitment.frontend.careers.track.form', ['userSlug' => $userSlug]);

                $emailData = [
                    'candidate_name' => $firstName . ' ' . $lastName,
                    'candidate_email' => $request->email,
                    'job_title' => $job->title,
                    'tracking_id' => $trackingId,
                    'tracking_link' => $trackingLink,
                ];

                EmailTemplate::sendEmailTemplate(
                    'Application Received',
                    [$request->email],
                    $emailData,
                    $userId
                );
            }

            // Redirect to success page with tracking ID
            return redirect()->route('recruitment.frontend.careers.application.success', [
                'userSlug' => $userSlug,
                'trackingId' => $trackingId
            ])->with('success', __('Application submitted successfully'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('An error occurred while submitting your application. Please try again.')]);
        }
    }
}
