<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\CandidateAssessment;
use Workdo\Recruitment\Http\Requests\StoreCandidateAssessmentRequest;
use Workdo\Recruitment\Http\Requests\UpdateCandidateAssessmentRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\Candidate;
use App\Models\User;
use Workdo\Recruitment\Events\CreateCandidateAssessment;
use Workdo\Recruitment\Events\UpdateCandidateAssessment;
use Workdo\Recruitment\Events\DestroyCandidateAssessment;

class CandidateAssessmentController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-candidate-assessments')) {
            $candidateassessments = CandidateAssessment::query()
                ->with(['candidate', 'conductedBy'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-candidate-assessments')) {
                        $q->where('candidate_assessments.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-candidate-assessments')) {
                        $q->where(function ($query) {
                            $query->where('candidate_assessments.creator_id', Auth::id())
                                ->orWhere('candidate_assessments.conducted_by', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('assessment_name'), function ($q) {
                    $searchTerm = request('assessment_name');
                    $q->where(function ($query) use ($searchTerm) {
                        // Search in assessment name and comments
                        $query->where('candidate_assessments.assessment_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('candidate_assessments.comments', 'like', '%' . $searchTerm . '%');
                        
                        // Search in candidate names
                        $query->orWhereHas('candidate', function($candidateQuery) use ($searchTerm) {
                            $candidateQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                          ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                                          ->orWhereRaw('CONCAT(first_name, " ", last_name) like ?', ['%' . $searchTerm . '%']);
                        });
                        
                        // Search in conducted by names
                        $query->orWhereHas('conductedBy', function($conductedByQuery) use ($searchTerm) {
                            $conductedByQuery->where('name', 'like', '%' . $searchTerm . '%');
                        });
                    });
                })

                ->when(request('pass_fail_status') !== null && request('pass_fail_status') !== '', fn($q) => $q->where('candidate_assessments.pass_fail_status', request('pass_fail_status')))
                ->when(request('start_date'), fn($q) => $q->where('candidate_assessments.assessment_date', '>=', request('start_date')))
                ->when(request('end_date'), fn($q) => $q->where('candidate_assessments.assessment_date', '<=', request('end_date')))
                ->when(request('sort'), function($q) {
                    $sort = request('sort');
                    $direction = request('direction', 'asc');

                    if ($sort === 'candidate_id') {
                        $q->join('candidates', 'candidate_assessments.candidate_id', '=', 'candidates.id')
                          ->orderBy('candidates.first_name', $direction)
                          ->orderBy('candidates.last_name', $direction)
                          ->select('candidate_assessments.*');
                    } elseif ($sort === 'conducted_by') {
                        $q->join('users', 'candidate_assessments.conducted_by', '=', 'users.id')
                          ->orderBy('users.name', $direction)
                          ->select('candidate_assessments.*');
                    } else {
                        $q->orderBy('candidate_assessments.' . $sort, $direction);
                    }
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Recruitment/CandidateAssessments/Index', [
                'candidateassessments' => $candidateassessments,
                'candidates' => Candidate::whereHas('interviews.interviewFeedbacks', function ($query) {
                    $query->whereIn('recommendation', [0, 1]); // Only Strong Hire & Hire
                })->where('created_by', creatorId())->select('id', 'first_name', 'last_name')->get()->map(function ($candidate) {
                    return [
                        'id' => $candidate->id,
                        'name' => $candidate->first_name . ' ' . $candidate->last_name
                    ];
                }),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-candidate-assessments')) {
            return Inertia::render('Recruitment/CandidateAssessments/Create', [
                'candidates' => Candidate::whereHas('interviews.interviewFeedbacks', function ($query) {
                    $query->whereIn('recommendation', [0, 1]); // Only Strong Hire & Hire
                })->where('created_by', creatorId())->select('id', 'first_name', 'last_name')->get()->map(function ($candidate) {
                    return [
                        'id' => $candidate->id,
                        'name' => $candidate->first_name . ' ' . $candidate->last_name
                    ];
                }),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return redirect()->route('recruitment.candidate-assessments.index')->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCandidateAssessmentRequest $request)
    {
        if (Auth::user()->can('create-candidate-assessments')) {
            $validated = $request->validated();

            $candidateassessment = new CandidateAssessment();
            $candidateassessment->assessment_name = $validated['assessment_name'];
            $candidateassessment->score = $validated['score'];
            $candidateassessment->max_score = $validated['max_score'];
            $candidateassessment->pass_fail_status = $validated['pass_fail_status'];
            $candidateassessment->comments = $validated['comments'];
            $candidateassessment->assessment_date = $validated['assessment_date'];
            $candidateassessment->candidate_id = $validated['candidate_id'];
            $candidateassessment->conducted_by = $validated['conducted_by'];

            $candidateassessment->creator_id = Auth::id();
            $candidateassessment->created_by = creatorId();
            $candidateassessment->save();

            CreateCandidateAssessment::dispatch($request, $candidateassessment);

            return redirect()->route('recruitment.candidate-assessments.index')->with('success', __('The candidate assessment has been created successfully.'));
        } else {
            return redirect()->route('recruitment.candidate-assessments.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(CandidateAssessment $candidateassessment)
    {
        if (Auth::user()->can('edit-candidate-assessments')) {
            return Inertia::render('Recruitment/CandidateAssessments/Edit', [
                'candidateassessment' => $candidateassessment,
                'candidates' => Candidate::whereHas('interviews.interviewFeedbacks', function ($query) {
                    $query->whereIn('recommendation', [0, 1]); // Only Strong Hire & Hire
                })->where('created_by', creatorId())->select('id', 'first_name', 'last_name')->get()->map(function ($candidate) {
                    return [
                        'id' => $candidate->id,
                        'name' => $candidate->first_name . ' ' . $candidate->last_name
                    ];
                }),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return redirect()->route('recruitment.candidate-assessments.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateCandidateAssessmentRequest $request, CandidateAssessment $candidateassessment)
    {
        if (Auth::user()->can('edit-candidate-assessments')) {
            $validated = $request->validated();

            $candidateassessment->assessment_name = $validated['assessment_name'];
            $candidateassessment->score = $validated['score'];
            $candidateassessment->max_score = $validated['max_score'];
            $candidateassessment->pass_fail_status = $validated['pass_fail_status'];
            $candidateassessment->comments = $validated['comments'];
            $candidateassessment->assessment_date = $validated['assessment_date'];
            $candidateassessment->candidate_id = $validated['candidate_id'];
            $candidateassessment->conducted_by = $validated['conducted_by'];

            $candidateassessment->save();

            UpdateCandidateAssessment::dispatch($request, $candidateassessment);

            return redirect()->back()->with('success', __('The candidate assessment details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.candidate-assessments.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(CandidateAssessment $candidateassessment)
    {
        if (Auth::user()->can('delete-candidate-assessments')) {
            DestroyCandidateAssessment::dispatch($candidateassessment);
            $candidateassessment->delete();

            return redirect()->back()->with('success', __('The candidate assessment has been deleted.'));
        } else {
            return redirect()->route('recruitment.candidate-assessments.index')->with('error', __('Permission denied'));
        }
    }
}
