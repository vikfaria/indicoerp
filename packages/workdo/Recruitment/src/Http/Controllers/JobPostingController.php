<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Http\Requests\StoreJobPostingRequest;
use Workdo\Recruitment\Http\Requests\UpdateJobPostingRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Models\Branch;
use Workdo\Hrm\Models\Department;
use Workdo\Recruitment\Models\CustomQuestion;
use Workdo\Recruitment\Models\JobType;
use Workdo\Recruitment\Models\JobLocation;
use Workdo\Recruitment\Events\CreateJobPosting;
use Workdo\Recruitment\Events\UpdateJobPosting;
use Workdo\Recruitment\Events\DestroyJobPosting;

class JobPostingController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-job-postings')){
            $jobpostings = JobPosting::query()
                ->leftJoin('branches', 'job_postings.branch_id', '=', 'branches.id')
                ->select('job_postings.*', 'branches.branch_name as branch_name')
                ->with([
                    'jobType:id,name,created_by',
                    'location:id,name'
                ])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-job-postings')) {
                        $q->where('job_postings.created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-job-postings')) {
                        $q->where('job_postings.creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('title'), function($q) {
                    $q->where(function($query) {
                    $query->where('job_postings.title', 'like', '%' . request('title') . '%')
                          ->orWhere('job_postings.description', 'like', '%' . request('title') . '%')
                          ->orWhere('job_postings.posting_code', 'like', '%' . request('title') . '%')
                          ->orWhere('branches.branch_name', 'like', '%' . request('title') . '%');
                    });
                })
                ->when(request('job_type_id') && request('job_type_id') !== 'all', fn($q) => $q->where('job_type_id', request('job_type_id')))
                ->when(request('location_id') && request('location_id') !== 'all', fn($q) => $q->where('location_id', request('location_id')))
                ->when(request('branch_id') && request('branch_id') !== 'all', fn($q) => $q->where('job_postings.branch_id', request('branch_id')))
                ->when(request('status') && request('status') !== 'all', fn($q) => $q->where('status', request('status')))
                ->when(request('sort'), function($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');

                    if ($sort === 'branch_name') {
                        $q->orderBy('branches.branch_name', $direction);
                    } elseif ($sort === 'salary_range') {
                        $q->orderBy('job_postings.min_salary', $direction)
                          ->orderBy('job_postings.max_salary', $direction);
                    } elseif ($sort === 'application_deadline') {
                        $q->orderBy('job_postings.application_deadline', $direction);
                    } else {
                        $q->orderBy('job_postings.' . $sort, $direction);
                    }
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Recruitment/JobPostings/Index', [
                'jobpostings' => $jobpostings,
                'jobtypes' => JobType::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'name')
                    ->get(),
                'joblocations' => JobLocation::where('created_by', creatorId())
                    ->where('status', 1)
                    ->select('id', 'name')
                    ->get(),
                'customquestions' => CustomQuestion::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'question', 'is_required')
                    ->get(),
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if(Auth::user()->can('create-job-postings')){
            return Inertia::render('Recruitment/JobPostings/Create', [
                'jobtypes' => JobType::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'name')
                    ->get(),
                'joblocations' => JobLocation::where('created_by', creatorId())
                    ->where('status', 1)
                    ->select('id', 'name')
                    ->get(),
                'customquestions' => CustomQuestion::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'question', 'is_required')
                    ->get(),
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function edit(JobPosting $jobposting)
    {
        if(Auth::user()->can('edit-job-postings')){
            return Inertia::render('Recruitment/JobPostings/Edit', [
                'jobposting' => $jobposting,
                'jobtypes' => JobType::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'name')
                    ->get(),
                'joblocations' => JobLocation::where('created_by', creatorId())
                    ->where('status', 1)
                    ->select('id', 'name')
                    ->get(),
                'customquestions' => CustomQuestion::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'question', 'is_required')
                    ->get(),
                'branches' => Branch::where('created_by', creatorId())->select('id', 'branch_name')->get(),
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreJobPostingRequest $request)
    {
        if(Auth::user()->can('create-job-postings')){
            $validated = $request->validated();

            // Generate application URL based on type
            $applicationUrl = null;
            if ($validated['job_application'] === 'existing') {
                $userSlug = Auth::user()->slug ?? 'demo';
                $applicationUrl = route('recruitment.frontend.careers.jobs.index', ['userSlug' => $userSlug]);
            } elseif ($validated['job_application'] === 'custom' && !empty($validated['application_url'])) {
                $applicationUrl = $validated['application_url'];
            }

            $jobposting = new JobPosting();
            $jobposting->code = $this->generateJobCode();
            $jobposting->posting_code = JobPosting::generatePostingCode();
            $jobposting->title = $validated['title'];
            $jobposting->position = $validated['position'];
            $jobposting->priority = $validated['priority'];
            $jobposting->job_application = $validated['job_application'];
            $jobposting->application_url = $applicationUrl;
            $jobposting->branch_id = $validated['branch_id'];
            $jobposting->min_experience = $validated['min_experience'];
            $jobposting->max_experience = $validated['max_experience'];
            $jobposting->min_salary = $validated['min_salary'];
            $jobposting->max_salary = $validated['max_salary'];
            $jobposting->description = $validated['description'];
            $jobposting->requirements = $validated['requirements'];
            $jobposting->skills = isset($validated['skills']) && is_array($validated['skills']) ? implode(',', $validated['skills']) : null;
            $jobposting->benefits = $validated['benefits'];
            $jobposting->terms_condition = $validated['terms_condition'];
            $jobposting->show_terms_condition = $validated['show_terms_condition'] ?? false;
            $jobposting->application_deadline = $validated['application_deadline'];
            $jobposting->is_published = $validated['is_published'] ?? false;
            $jobposting->publish_date = $validated['publish_date'];
            $jobposting->is_featured = $validated['is_featured'] ?? false;
            $jobposting->status = $validated['status'];
            $jobposting->job_type_id = $validated['job_type_id'];
            $jobposting->location_id = $validated['location_id'];
            $jobposting->applicant = json_encode($validated['applicant'] ?? []);
            $jobposting->visibility = json_encode($validated['visibility'] ?? []);
            $jobposting->custom_questions = json_encode($validated['custom_questions'] ?? []);

            $jobposting->creator_id = Auth::id();
            $jobposting->created_by = creatorId();
            $jobposting->save();

            CreateJobPosting::dispatch($request, $jobposting);

            return redirect()->route('recruitment.job-postings.index')->with('success', __('The job posting has been created successfully.'));
        }
        else{
            return redirect()->route('recruitment.job-postings.index')->with('error', __('Permission denied'));
        }
    }

    private function generateJobCode()
    {
        $prefix = 'JOB';
        $lastJob = JobPosting::where('created_by', creatorId())
            ->where('code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob && $lastJob->code) {
            $lastNumber = (int) substr($lastJob->code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobposting)
    {
        if(Auth::user()->can('edit-job-postings')){
            $validated = $request->validated();

            // Generate application URL based on type
            $applicationUrl = null;
            if ($validated['job_application'] === 'existing') {
                $userSlug = Auth::user()->slug ?? 'demo';
                $applicationUrl = route('recruitment.frontend.careers.jobs.index', ['userSlug' => $userSlug]);
            } elseif ($validated['job_application'] === 'custom' && !empty($validated['application_url'])) {
                $applicationUrl = $validated['application_url'];
            }

            $jobposting->title = $validated['title'];
            $jobposting->position = $validated['position'];
            $jobposting->priority = $validated['priority'];
            $jobposting->job_application = $validated['job_application'];
            $jobposting->application_url = $applicationUrl;
            $jobposting->branch_id = $validated['branch_id'];
            $jobposting->min_experience = $validated['min_experience'];
            $jobposting->max_experience = $validated['max_experience'];
            $jobposting->min_salary = $validated['min_salary'];
            $jobposting->max_salary = $validated['max_salary'];
            $jobposting->description = $validated['description'];
            $jobposting->requirements = $validated['requirements'];
            $jobposting->skills = isset($validated['skills']) && is_array($validated['skills']) ? implode(',', $validated['skills']) : null;
            $jobposting->benefits = $validated['benefits'];
            $jobposting->terms_condition = $validated['terms_condition'];
            $jobposting->show_terms_condition = $validated['show_terms_condition'] ?? false;
            $jobposting->application_deadline = $validated['application_deadline'];
            $jobposting->is_published = $validated['is_published'];
            $jobposting->publish_date = $validated['publish_date'];
            $jobposting->is_featured = $validated['is_featured'];
            $jobposting->status = $validated['status'];
            $jobposting->job_type_id = $validated['job_type_id'];
            $jobposting->location_id = $validated['location_id'];
            $jobposting->applicant = json_encode($validated['applicant'] ?? []);
            $jobposting->visibility = json_encode($validated['visibility'] ?? []);
            $jobposting->custom_questions = json_encode($validated['custom_questions'] ?? []);

            $jobposting->save();

            UpdateJobPosting::dispatch($request, $jobposting);

            return redirect()->back()->with('success', __('The job posting details are updated successfully.'));
        }
        else{
            return redirect()->route('recruitment.job-postings.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(JobPosting $jobposting)
    {
        if(Auth::user()->can('delete-job-postings')){
            DestroyJobPosting::dispatch($jobposting);

            $jobposting->delete();

            return redirect()->back()->with('success', __('The job posting has been deleted.'));
        }
        else{
            return redirect()->route('recruitment.job-postings.index')->with('error', __('Permission denied'));
        }
    }

    public function togglePublish(JobPosting $jobposting)
    {
        if(Auth::user()->can('publish-job-postings')){
            if($jobposting->status === 'draft' || $jobposting->status === '0') {
                $jobposting->status = 'active';
                $jobposting->is_published = true;
                $jobposting->publish_date = now()->format('Y-m-d');
            } else {
                $jobposting->status = 'draft';
                $jobposting->is_published = false;
                $jobposting->publish_date = null;
            }
            $jobposting->save();

            return redirect()->back()->with('success', __('Job posting status updated successfully.'));
        }
        else{
            return redirect()->route('recruitment.job-postings.index')->with('error', __('Permission denied'));
        }
    }

    public function show(JobPosting $jobposting)
    {
        if(Auth::user()->can('view-job-postings')){
            // Check if user can access this specific job posting
            if (!$this->canAccessJobPosting($jobposting)) {
                return redirect()->route('recruitment.job-postings.index')->with('error', __('Permission denied'));
            }

            $jobposting = JobPosting::leftJoin('branches', 'job_postings.branch_id', '=', 'branches.id')
                ->select('job_postings.*', 'branches.branch_name as branch_name')
                ->where('job_postings.id', $jobposting->id)
                ->with([
                    'jobType:id,name',
                    'location:id,name'
                ])
                ->first();

            return Inertia::render('Recruitment/JobPostings/Show', [
                'jobposting' => $jobposting
            ]);
        }
        else{
            return redirect()->route('recruitment.job-postings.index')->with('error', __('Permission denied'));
        }
    }

    private function canAccessJobPosting(JobPosting $jobposting)
    {
        if (Auth::user()->can('manage-any-job-postings')) {
            return $jobposting->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-job-postings')) {
            return $jobposting->creator_id == Auth::id();
        } else {
            return false;
        }
    }

    public function getJobPostingSettings($jobPostingId)
    {
        if(Auth::user()->can('manage-candidates') || Auth::user()->can('create-candidates')){
            $jobPosting = JobPosting::where('id', $jobPostingId)
                ->where('created_by', creatorId())
                ->select('applicant', 'visibility')
                ->first();

            if (!$jobPosting) {
                return response()->json(['applicant' => [], 'visibility' => []], 404);
            }

            return response()->json([
                'applicant' => $jobPosting->applicant ?? [],
                'visibility' => $jobPosting->visibility ?? []
            ]);
        }
        else{
            return response()->json([], 403);
        }
    }
}
