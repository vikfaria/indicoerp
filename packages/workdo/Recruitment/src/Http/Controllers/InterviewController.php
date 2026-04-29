<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\Interview;
use Workdo\Recruitment\Http\Requests\StoreInterviewRequest;
use Workdo\Recruitment\Http\Requests\UpdateInterviewRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Models\InterviewRound;
use Workdo\Recruitment\Models\InterviewType;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Workdo\Recruitment\Events\CreateInterview;
use Workdo\Recruitment\Events\UpdateInterview;
use Workdo\Recruitment\Events\DestroyInterview;

class InterviewController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-interviews')) {
            $interviews = Interview::query()
                ->with([
                    'candidate',
                    'jobPosting:id,title,location_id',
                    'jobPosting.location:id,name,remote_work',
                    'interviewRound:id,name',
                    'interviewType:id,name'
                ])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-interviews')) {
                        $q->where('interviews.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-interviews')) {
                        $q->where(function ($query) {
                            $query->where('interviews.creator_id', Auth::id())
                                ->orWhereJsonContains('interviews.interviewer_ids', (string) Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('location'), function ($q) {
                    $searchTerm = request('location');
                    $q->where(function ($query) use ($searchTerm) {
                        $query->where('interviews.location', 'like', '%' . $searchTerm . '%')
                            ->orWhereHas('candidate', function ($candidateQuery) use ($searchTerm) {
                                $candidateQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
                            })
                            ->orWhereHas('jobPosting', function ($jobQuery) use ($searchTerm) {
                                $jobQuery->where('title', 'like', '%' . $searchTerm . '%');
                            })
                            ->orWhereHas('interviewRound', function ($roundQuery) use ($searchTerm) {
                                $roundQuery->where('name', 'like', '%' . $searchTerm . '%');
                            })
                            ->orWhereHas('interviewType', function ($typeQuery) use ($searchTerm) {
                                $typeQuery->where('name', 'like', '%' . $searchTerm . '%');
                            });
                    });
                })
                ->when(request('interview_date'), fn($q) => $q->whereDate('interviews.scheduled_date', request('interview_date')))
                ->when(request('feedback') !== null && request('feedback') !== '', function ($q) {
                    if (request('feedback') === 'submitted') {
                        $q->where('interviews.feedback_submitted', 1);
                    } elseif (request('feedback') === 'pending') {
                        $q->where(function ($query) {
                            $query->where('interviews.feedback_submitted', 0)
                                ->orWhereNull('interviews.feedback_submitted');
                        });
                    }
                })
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('interviews.status', request('status')))
                ->when(request('interview_type_id') && request('interview_type_id') !== 'all', fn($q) => $q->where('interviews.interview_type_id', request('interview_type_id')))
                ->when(request('sort'), function ($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');

                    switch ($sort) {
                        case 'candidate_name':
                            $q->leftJoin('candidates', 'interviews.candidate_id', '=', 'candidates.id')
                                ->select('interviews.*', \DB::raw("CONCAT(candidates.first_name, ' ', candidates.last_name) as candidate_name"))
                                ->orderBy('candidate_name', $direction);
                            break;
                        case 'round_name':
                            $q->leftJoin('interview_rounds', 'interviews.round_id', '=', 'interview_rounds.id')
                                ->select('interviews.*', 'interview_rounds.name as round_name')
                                ->orderBy('round_name', $direction);
                            break;
                        case 'interview_type_name':
                            $q->leftJoin('interview_types', 'interviews.interview_type_id', '=', 'interview_types.id')
                                ->select('interviews.*', 'interview_types.name as interview_type_name')
                                ->orderBy('interview_type_name', $direction);
                            break;
                        case 'scheduled_date':
                            $q->select('interviews.*')->orderBy('interviews.scheduled_date', $direction);
                            break;
                        default:
                            $q->select('interviews.*')->orderBy('interviews.' . $sort, $direction);
                    }
                }, fn($q) => $q->select('interviews.*')->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            // Process interviewer names for each interview
            $interviews->getCollection()->transform(function ($interview) {
                if ($interview->interviewer_ids) {
                    $interviewerIds = is_array($interview->interviewer_ids) ? $interview->interviewer_ids : json_decode($interview->interviewer_ids, true);
                    if ($interviewerIds) {
                        $interviewerNames = User::emp()->whereIn('id', $interviewerIds)->pluck('name')->toArray();
                        $interview->interviewer_names = implode(', ', $interviewerNames);
                    }
                }
                return $interview;
            });

            return Inertia::render('Recruitment/Interviews/Index', [
                'interviews' => $interviews,
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where('status', '2')
                    ->select('id', 'first_name', 'last_name')->get()->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->first_name . ' ' . $candidate->last_name,
                            'first_name' => $candidate->first_name,
                            'last_name' => $candidate->last_name
                        ];
                    }),
                'jobpostings' => JobPosting::where('created_by', creatorId())->where('is_published', 1)->where('status', 'active')->select('id', 'title')->get()->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'name' => $job->title,
                        'title' => $job->title
                    ];
                }),
                'interviewrounds' => InterviewRound::where('created_by', creatorId())->where('status', 0)->select('id', 'name')->get(),
                'interviewtypes' => InterviewType::where('created_by', creatorId())->where('is_active', 1)->select('id', 'name')->get(),
                'employees' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-interviews')) {
            return Inertia::render('Recruitment/Interviews/Create', [
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where('status', '2')
                    ->select('id', 'first_name', 'last_name')->get()->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->first_name . ' ' . $candidate->last_name,
                            'first_name' => $candidate->first_name,
                            'last_name' => $candidate->last_name
                        ];
                    }),
                'jobpostings' => JobPosting::where('created_by', creatorId())->where('is_published', 1)->where('status', 'active')->select('id', 'title')->get(),
                'interviewrounds' => InterviewRound::where('created_by', creatorId())->where('status', 0)->select('id', 'name')->get(),
                'interviewtypes' => InterviewType::where('created_by', creatorId())->where('is_active', 1)->select('id', 'name')->get(),
                'employees' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return redirect()->route('recruitment.interviews.index')->with('error', __('Permission denied'));
        }
    }

    public function store(StoreInterviewRequest $request)
    {
        if (Auth::user()->can('create-interviews')) {
            $validated = $request->validated();

            $candidate = Candidate::with(['job_posting.location'])->find($validated['candidate_id']);

            $interview = new Interview();
            $interview->candidate_id = $validated['candidate_id'];
            $interview->job_id = $candidate->job_id;
            $interview->round_id = $validated['round_id'];
            $interview->interview_type_id = $validated['interview_type_id'];
            $interview->scheduled_date = $validated['scheduled_date'];
            $interview->scheduled_time = $validated['scheduled_time'];
            $interview->duration = $validated['duration'];

            // Set location to 'Online' for remote work jobs
            if ($candidate->job_posting && $candidate->job_posting->location && $candidate->job_posting->location->remote_work) {
                $interview->location = 'Online';
            } else {
                $interview->location = $validated['location'];
            }

            $interview->meeting_link = $validated['meeting_link'];
            $interview->interviewer_ids = $validated['interviewer_ids'] ?? [];
            $interview->status = $validated['status'];

            $interview->creator_id = Auth::id();
            $interview->created_by = creatorId();
            $interview->save();

            CreateInterview::dispatch($request, $interview);

            return redirect()->route('recruitment.interviews.index')->with('success', __('The interview has been created successfully.'));
        } else {
            return redirect()->route('recruitment.interviews.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(Interview $interview)
    {
        if (Auth::user()->can('edit-interviews')) {
            return Inertia::render('Recruitment/Interviews/Edit', [
                'interview' => $interview,
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where('status', '2')
                    ->select('id', 'first_name', 'last_name')->get()->map(function ($candidate) {
                        return [
                            'id' => $candidate->id,
                            'name' => $candidate->first_name . ' ' . $candidate->last_name,
                            'first_name' => $candidate->first_name,
                            'last_name' => $candidate->last_name
                        ];
                    }),
                'jobpostings' => JobPosting::where('created_by', creatorId())->where('is_published', 1)->where('status', 'active')->select('id', 'title')->get(),
                'interviewrounds' => InterviewRound::where('created_by', creatorId())->where('status', 0)->select('id', 'name')->get(),
                'interviewtypes' => InterviewType::where('created_by', creatorId())->where('is_active', 1)->select('id', 'name')->get(),
                'employees' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return redirect()->route('recruitment.interviews.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateInterviewRequest $request, Interview $interview)
    {
        if (Auth::user()->can('edit-interviews')) {
            $validated = $request->validated();

            $candidate = Candidate::with(['job_posting.location'])->find($validated['candidate_id']);
            if (!$candidate) {
                return redirect()->back()->with('error', __('Candidate not found'));
            }

            // Store old status to check for changes
            $oldStatus = $interview->status;

            $interview->candidate_id = $validated['candidate_id'];
            $interview->job_id = $candidate->job_id;
            $interview->round_id = $validated['round_id'];
            $interview->interview_type_id = $validated['interview_type_id'];
            $interview->scheduled_date = $validated['scheduled_date'];
            $interview->scheduled_time = $validated['scheduled_time'];
            $interview->duration = $validated['duration'];
            $interview->location = $validated['location'];
            $interview->meeting_link = $validated['meeting_link'];
            $interview->interviewer_ids = $validated['interviewer_ids'] ?? [];
            $interview->status = $validated['status'];

            $interview->save();

            // Auto-update candidate status when interview is completed
            if ($oldStatus !== '1' && $validated['status'] === '1' && $candidate->status === '2') {
                $candidate->status = '3'; // Change from Interview to Offer
                $candidate->save();
            }

            UpdateInterview::dispatch($request, $interview);

            return redirect()->back()->with('success', __('The interview details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.interviews.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Interview $interview)
    {
        if (Auth::user()->can('delete-interviews')) {
            DestroyInterview::dispatch($interview);
            $interview->delete();

            return redirect()->back()->with('success', __('The interview has been deleted.'));
        } else {
            return redirect()->route('recruitment.interviews.index')->with('error', __('Permission denied'));
        }
    }

    public function getInterviewRoundsByCandidate($candidateId)
    {
        if (Auth::user()->can('manage-interview-rounds') || Auth::user()->can('create-interviews') || Auth::user()->can('edit-interviews')) {
            $candidate = Candidate::find($candidateId);
            if (!$candidate || !$candidate->job_id) {
                return response()->json([]);
            }

            $rounds = InterviewRound::where('job_id', $candidate->job_id)
                ->where('status', 0)
                ->where('created_by', creatorId())
                ->select('id', 'name')
                ->get();

            return response()->json($rounds);
        } else {
            return response()->json([], 403);
        }
    }
}
