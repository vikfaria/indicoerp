<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Http\Requests\StoreCandidateRequest;
use Workdo\Recruitment\Http\Requests\UpdateCandidateRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\CandidateSources;
use Workdo\Recruitment\Models\CustomQuestion;
use Workdo\Recruitment\Events\CreateCandidate;
use Workdo\Recruitment\Events\UpdateCandidate;
use Workdo\Recruitment\Events\DestroyCandidate;

class CandidateController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-candidates')) {
            $candidates = Candidate::query()
                ->with(['job_posting', 'candidate_source'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-candidates')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-candidates')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $searchTerm = request('name');
                    $q->where(function ($query) use ($searchTerm) {
                        $query->where('first_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('email', 'like', '%' . $searchTerm . '%')
                              ->orWhere('tracking_id', 'like', '%' . $searchTerm . '%')
                              ->orWhere('skills', 'like', '%' . $searchTerm . '%')
                              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
                    });
                })
                ->when(request('job_id') && request('job_id') !== 'all', fn($q) => $q->where('job_id', request('job_id')))
                ->when(request('source_id') && request('source_id') !== 'all', fn($q) => $q->where('source_id', request('source_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('status', request('status')))
                ->when(request('application_date'), fn($q) => $q->whereDate('application_date', request('application_date')))
                ->when(request('sort'), function($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');
                    if ($sort === 'name') {
                        return $q->orderBy('first_name', $direction)->orderBy('last_name', $direction);
                    }
                    return $q->orderBy($sort, $direction);
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Recruitment/Candidates/Index', [
                'candidates' => $candidates,
                'jobpostings' => JobPosting::where('created_by', creatorId())->where('is_published', 1)->where('status', 'active')->select('id', 'title')->get(),
                'candidatesources' => CandidateSources::where('created_by', creatorId())->where('is_active', 1)->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-candidates')) {
            return Inertia::render('Recruitment/Candidates/Create', [
                'jobpostings' => JobPosting::where('created_by', creatorId())
                    ->where('is_published', 1)
                    ->where('status', 'active')
                    ->select('id', 'title')
                    ->get(),
                'candidatesources' => CandidateSources::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'name')
                    ->get(),
                'customquestions' => CustomQuestion::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'question', 'type', 'options', 'is_required')
                    ->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function edit(Candidate $candidate)
    {
        if (Auth::user()->can('edit-candidates')) {
            // Check if user can access this specific candidate
            if (!$this->canAccessCandidate($candidate)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            return Inertia::render('Recruitment/Candidates/Edit', [
                'candidate' => $candidate,
                'jobpostings' => JobPosting::where('created_by', creatorId())
                    ->where('is_published', 1)
                    ->where('status', 'active')
                    ->select('id', 'title')
                    ->get(),
                'candidatesources' => CandidateSources::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'name')
                    ->get(),
                'customquestions' => CustomQuestion::where('created_by', creatorId())
                    ->where('is_active', 1)
                    ->select('id', 'question', 'type', 'options', 'is_required')
                    ->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCandidateRequest $request)
    {
        if (Auth::user()->can('create-candidates')) {
            $validated = $request->validated();

            // Handle file uploads
            $resumePath = null;
            $coverLetterPath = null;
            $profilePath = null;

            // Handle profile_url (extract filename from path)
            if (!empty($request->input('profile_url'))) {
                $profilePath = basename($request->input('profile_url'));
            }

            if ($request->hasFile('resume') && $request->file('resume')->isValid()) {
                $filenameWithExt = $request->file('resume')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('resume')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'resume', $fileNameToStore, 'candidates/resumes');
                if ($upload['flag'] == 1) {
                    $resumePath = $upload['url'];
                } else {
                    \Log::error('Resume upload failed', $upload);
                }
            }

            if ($request->hasFile('cover_letter') && $request->file('cover_letter')->isValid()) {
                $filenameWithExt = $request->file('cover_letter')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('cover_letter')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'cover_letter', $fileNameToStore, 'candidates/cover_letters');
                if ($upload['flag'] == 1) {
                    $coverLetterPath = $upload['url'];
                } else {
                    \Log::error('Cover letter upload failed', $upload);
                }
            }

            // Extract custom question answers
            $customAnswers = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'custom_question_') && $value !== null) {
                    $customAnswers[$key] = $value;
                }
            }

            $trackingId = Candidate::generateTrackingId();

            $candidate = new Candidate();
            $candidate->first_name = $validated['first_name'];
            $candidate->last_name = $validated['last_name'];
            $candidate->email = $validated['email'];
            $candidate->phone = $validated['phone'];
            $candidate->gender = $validated['gender'] ?? null;
            $candidate->dob = $validated['dob'] ?? null;
            $candidate->country = $validated['country'] ?? null;
            $candidate->state = $validated['state'] ?? null;
            $candidate->city = $validated['city'] ?? null;
            $candidate->current_company = $validated['current_company'];
            $candidate->current_position = $validated['current_position'];
            $candidate->experience_years = $validated['experience_years'];
            $candidate->current_salary = $validated['current_salary'];
            $candidate->expected_salary = $validated['expected_salary'];
            $candidate->notice_period = $validated['notice_period'];
            $candidate->skills = $validated['skills'];
            $candidate->education = $validated['education'];
            $candidate->portfolio_url = $validated['portfolio_url'];
            $candidate->linkedin_url = $validated['linkedin_url'];
            $candidate->profile_path = $profilePath;
            $candidate->resume_path = $resumePath;
            $candidate->cover_letter_path = $coverLetterPath;
            $candidate->status = $validated['status'];
            $candidate->application_date = $validated['application_date'];
            $candidate->custom_question = !empty($customAnswers) ? json_encode($customAnswers) : null;
            $candidate->job_id = $validated['job_id'];
            $candidate->source_id = $validated['source_id'];

            $candidate->creator_id = Auth::id();
            $candidate->created_by = creatorId();
            $candidate->tracking_id = $trackingId;
            $candidate->save();

            CreateCandidate::dispatch($request, $candidate);

            return redirect()->route('recruitment.candidates.index')->with('success', __('The candidate has been created successfully.'));
        } else {
            return redirect()->route('recruitment.candidates.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate)
    {
        if (Auth::user()->can('edit-candidates')) {
            $validated = $request->validated();

            // Handle profile_url (extract filename from path)
            if (!empty($request->input('profile_url'))) {
                $candidate->profile_path = basename($request->input('profile_url'));
            }

            if ($request->hasFile('resume') && $request->file('resume')->isValid()) {
                $filenameWithExt = $request->file('resume')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('resume')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'resume', $fileNameToStore, 'candidates/resumes');
                if ($upload['flag'] == 1) {
                    $candidate->resume_path = $upload['url'];
                }
            }

            if ($request->hasFile('cover_letter') && $request->file('cover_letter')->isValid()) {
                $filenameWithExt = $request->file('cover_letter')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('cover_letter')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $upload = upload_file($request, 'cover_letter', $fileNameToStore, 'candidates/cover_letters');
                if ($upload['flag'] == 1) {
                    $candidate->cover_letter_path = $upload['url'];
                }
            }

            // Extract custom question answers
            $customAnswers = [];
            foreach ($validated as $key => $value) {
                if (str_starts_with($key, 'custom_question_') && $value !== null) {
                    $customAnswers[$key] = $value;
                }
            }

            $candidate->first_name = $validated['first_name'];
            $candidate->last_name = $validated['last_name'];
            $candidate->email = $validated['email'];
            $candidate->phone = $validated['phone'];
            $candidate->gender = $validated['gender'] ?? $candidate->gender;
            $candidate->dob = $validated['dob'] ?? $candidate->dob;
            $candidate->country = $validated['country'] ?? $candidate->country;
            $candidate->state = $validated['state'] ?? $candidate->state;
            $candidate->city = $validated['city'] ?? $candidate->city;
            $candidate->current_company = $validated['current_company'];
            $candidate->current_position = $validated['current_position'];
            $candidate->experience_years = $validated['experience_years'];
            $candidate->current_salary = $validated['current_salary'];
            $candidate->expected_salary = $validated['expected_salary'];
            $candidate->notice_period = $validated['notice_period'];
            $candidate->skills = $validated['skills'];
            $candidate->education = $validated['education'];
            $candidate->portfolio_url = $validated['portfolio_url'];
            $candidate->linkedin_url = $validated['linkedin_url'];
            $candidate->application_date = $validated['application_date'];
            $candidate->custom_question = !empty($customAnswers) ? json_encode($customAnswers) : null;
            $candidate->job_id = $validated['job_id'];
            $candidate->source_id = $validated['source_id'];

            $candidate->save();

            UpdateCandidate::dispatch($request, $candidate);

            return redirect()->back()->with('success', __('The candidate details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.candidates.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Candidate $candidate)
    {
        if (Auth::user()->can('delete-candidates')) {
            // Delete profile photo file if exists
            if ($candidate->profile_path) {
                delete_file($candidate->profile_path);
            }

            // Delete resume file if exists
            if ($candidate->resume_path) {
                delete_file($candidate->resume_path);
            }

            // Delete cover letter file if exists
            if ($candidate->cover_letter_path) {
                delete_file($candidate->cover_letter_path);
            }

            DestroyCandidate::dispatch($candidate);
            $candidate->delete();

            return redirect()->back()->with('success', __('The candidate has been deleted.'));
        } else {
            return redirect()->route('recruitment.candidates.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Candidate $candidate)
    {
        if (Auth::user()->can('view-candidates')) {
            // Check if user can access this specific candidate
            if (!$this->canAccessCandidate($candidate)) {
                return redirect()->route('recruitment.candidates.index')->with('error', __('Permission denied'));
            }

            $candidate->load(['job_posting', 'candidate_source']);

            // Load custom questions for this candidate's job posting
            $customQuestions = [];
            if ($candidate->job_posting && $candidate->job_posting->custom_questions) {
                // Ensure custom_questions is an array
                $customQuestionIds = $candidate->job_posting->custom_questions;
                if (is_string($customQuestionIds)) {
                    $customQuestionIds = json_decode($customQuestionIds, true) ?: [];
                }
                if (is_array($customQuestionIds) && !empty($customQuestionIds)) {
                    $customQuestions = CustomQuestion::whereIn('id', $customQuestionIds)
                        ->where('created_by', creatorId())
                        ->select('id', 'question')
                        ->get()
                        ->keyBy('id');
                }
            }

            return Inertia::render('Recruitment/Candidates/Show', [
                'candidate' => $candidate,
                'customQuestions' => $customQuestions
            ]);
        } else {
            return redirect()->route('recruitment.candidates.index')->with('error', __('Permission denied'));
        }
    }

    private function canAccessCandidate(Candidate $candidate)
    {
        if (Auth::user()->can('manage-any-candidates')) {
            return $candidate->created_by == creatorId();
        } elseif (Auth::user()->can('manage-own-candidates')) {
            return $candidate->creator_id == Auth::id();
        } else {
            return false;
        }
    }

    public function getSourcesByJobPosting($jobpostingId)
    {
        if (Auth::user()->can('manage-candidate-sources') || Auth::user()->can('create-candidates') || Auth::user()->can('edit-candidates')) {
            $sources = CandidateSources::where('jobPosting_id', $jobpostingId)
                ->where('created_by', creatorId())
                ->where('is_active', true)
                ->select('id', 'name')
                ->get();

            return response()->json($sources);
        } else {
            return response()->json([], 403);
        }
    }

    public function getCustomQuestionsByJobPosting($jobpostingId)
    {
        if (Auth::user()->can('manage-custom-questions') || Auth::user()->can('create-candidates') || Auth::user()->can('edit-candidates')) {
            $jobPosting = JobPosting::where('id', $jobpostingId)
                ->where('is_published', 1)
                ->where('status', 'active')
                ->where('created_by', creatorId())
                ->first();

            if (!$jobPosting || !$jobPosting->custom_questions) {
                return response()->json([]);
            }

            // Ensure custom_questions is an array
            $customQuestionIds = $jobPosting->custom_questions;
            if (is_string($customQuestionIds)) {
                $customQuestionIds = json_decode($customQuestionIds, true) ?: [];
            }
            if (!is_array($customQuestionIds) || empty($customQuestionIds)) {
                return response()->json([]);
            }

            $customQuestions = CustomQuestion::whereIn('id', $customQuestionIds)
                ->where('is_active', true)
                ->where('created_by', creatorId())
                ->orderBy('sort_order')
                ->select('id', 'question', 'type', 'options', 'is_required', 'sort_order')
                ->get();

            return response()->json($customQuestions);
        } else {
            return response()->json([], 403);
        }
    }

    public function getCandidateJobLocation($candidateId)
    {
        if (Auth::user()->can('manage-job-locations') || Auth::user()->can('create-candidates') || Auth::user()->can('edit-candidates')) {
            $candidate = Candidate::with(['job_posting.location:id,remote_work'])
                ->where('id', $candidateId)
                ->where('created_by', creatorId())
                ->first();

            if (!$candidate || !$candidate->job_posting || !$candidate->job_posting->location) {
                return response()->json(['remote_work' => false]);
            }

            return response()->json([
                'remote_work' => $candidate->job_posting->location->remote_work
            ]);
        } else {
            return response()->json(['remote_work' => false], 403);
        }
    }

    public function updateStatus(Candidate $candidate)
    {
        if (Auth::user()->can('edit-candidates')) {
            // Check if user can access this specific candidate
            if (!$this->canAccessCandidate($candidate)) {
                return redirect()->back()->with('error', __('Permission denied'));
            }

            $validated = request()->validate([
                'status' => 'required|in:0,1,2,3,4,5'
            ]);

            $candidate->status = $validated['status'];
            $candidate->save();

            return redirect()->back()->with('success', __('The status has been updated successfully'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
