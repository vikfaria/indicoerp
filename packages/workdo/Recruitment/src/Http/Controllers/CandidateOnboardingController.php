<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\CandidateOnboarding;
use Workdo\Recruitment\Http\Requests\StoreCandidateOnboardingRequest;
use Workdo\Recruitment\Http\Requests\UpdateCandidateOnboardingRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\Candidate;
use Workdo\Recruitment\Models\OnboardingChecklist;
use App\Models\User;
use Workdo\Recruitment\Events\CreateCandidateOnboarding;
use Workdo\Recruitment\Events\UpdateCandidateOnboarding;
use Workdo\Recruitment\Events\DestroyCandidateOnboarding;

class CandidateOnboardingController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-candidate-onboardings')) {
            $candidateonboardings = CandidateOnboarding::query()
                ->with([
                    'candidate:id,first_name,last_name,email',
                    'checklist:id,name',
                    'buddy:id,name'
                ])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-candidate-onboardings')) {
                        $q->where('candidate_onboardings.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-candidate-onboardings')) {
                        $q->where(function ($query) {
                            $query->where('candidate_onboardings.creator_id', Auth::id())
                                ->orWhere('candidate_onboardings.buddy_employee_id', Auth::id());
                        });
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('search'), function ($q) {
                    $q->where(function ($query) {
                        $searchTerm = request('search');
                        $query->whereHas('candidate', function ($subQuery) use ($searchTerm) {
                            $subQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('email', 'like', '%' . $searchTerm . '%')
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%']);
                        })->orWhereHas('checklist', function ($subQuery) use ($searchTerm) {
                            $subQuery->where('name', 'like', '%' . $searchTerm . '%');
                        })->orWhereHas('buddy', function ($subQuery) use ($searchTerm) {
                            $subQuery->where('name', 'like', '%' . $searchTerm . '%');
                        });
                    });
                })
                ->when(request('candidate_id') && request('candidate_id') !== 'all', fn($q) => $q->where('candidate_onboardings.candidate_id', request('candidate_id')))
                ->when(request('checklist_id') && request('checklist_id') !== 'all', fn($q) => $q->where('candidate_onboardings.checklist_id', request('checklist_id')))
                ->when(request('buddy_employee_id') && request('buddy_employee_id') !== 'all' && Auth::user()->can('manage-any-candidate-onboardings'), fn($q) => $q->where('candidate_onboardings.buddy_employee_id', request('buddy_employee_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('candidate_onboardings.status', request('status')))
                ->when(request('start_date_from'), fn($q) => $q->where('candidate_onboardings.start_date', '>=', request('start_date_from')))
                ->when(request('start_date_to'), fn($q) => $q->where('candidate_onboardings.start_date', '<=', request('start_date_to')))
                ->when(request('sort'), function($q) {
                    $sortField = request('sort');
                    $direction = request('direction', 'asc');

                    switch($sortField) {
                        case 'candidate.name':
                            $q->join('candidates', 'candidate_onboardings.candidate_id', '=', 'candidates.id')
                              ->orderBy('candidates.first_name', $direction)
                              ->orderBy('candidates.last_name', $direction)
                              ->select('candidate_onboardings.*');
                            break;
                        case 'checklist.name':
                            $q->join('onboarding_checklists', 'candidate_onboardings.checklist_id', '=', 'onboarding_checklists.id')
                              ->orderBy('onboarding_checklists.name', $direction)
                              ->select('candidate_onboardings.*');
                            break;
                        default:
                            $q->orderBy($sortField, $direction);
                            break;
                    }
                }, fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Recruitment/CandidateOnboardings/Index', [
                'candidateonboardings' => $candidateonboardings,
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where('status', 4)
                    ->select('id', 'first_name', 'last_name', 'email', 'status')
                    ->get()->map(function ($candidate) {
                        $candidate->name = $candidate->first_name . ' ' . $candidate->last_name;
                        return $candidate;
                    }),
                'onboardingchecklists' => OnboardingChecklist::where('created_by', creatorId())->where('status', 1)->select('id', 'name')->where('status', 1)->get(),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-candidate-onboardings')) {
            return Inertia::render('Recruitment/CandidateOnboardings/Create', [
                'candidates' => Candidate::where('created_by', creatorId())
                    ->where('status', 4)
                    ->select('id', 'first_name', 'last_name', 'email', 'status')
                    ->get()->map(function ($candidate) {
                        $candidate->name = $candidate->first_name . ' ' . $candidate->last_name;
                        return $candidate;
                    }),
                'onboardingchecklists' => OnboardingChecklist::where('created_by', creatorId())->where('status', 1)->select('id', 'name')->where('status', 1)->get(),
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return redirect()->route('recruitment.candidate-onboardings.index')->with('error', __('Permission denied'));
        }
    }

    public function store(StoreCandidateOnboardingRequest $request)
    {
        if (Auth::user()->can('create-candidate-onboardings')) {
            $validated = $request->validated();

            $candidateonboarding = new CandidateOnboarding();
            $candidateonboarding->candidate_id = $validated['candidate_id'];
            $candidateonboarding->checklist_id = $validated['checklist_id'];
            $candidateonboarding->start_date = $validated['start_date'];
            $candidateonboarding->buddy_employee_id = $validated['buddy_employee_id'] ?: null;
            $candidateonboarding->status = 'Pending';

            $candidateonboarding->creator_id = Auth::id();
            $candidateonboarding->created_by = creatorId();
            $candidateonboarding->save();

            CreateCandidateOnboarding::dispatch($request, $candidateonboarding);

            return redirect()->route('recruitment.candidate-onboardings.index')->with('success', __('The candidate onboarding has been created successfully.'));
        } else {
            return redirect()->route('recruitment.candidate-onboardings.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(CandidateOnboarding $candidateonboarding)
    {
        if (Auth::user()->can('edit-candidate-onboardings')) {
            // Get all candidates with status 4, plus the current candidate if different
            $candidates = Candidate::where('created_by', creatorId())
                ->where(function($q) use ($candidateonboarding) {
                    $q->where('status', 4)
                      ->orWhere('id', $candidateonboarding->candidate_id);
                })
                ->select('id', 'first_name', 'last_name', 'email', 'status')
                ->get()->map(function ($candidate) {
                    $candidate->name = $candidate->first_name . ' ' . $candidate->last_name;
                    return $candidate;
                });

            // Get all active checklists, plus the current checklist if different
            $checklists = OnboardingChecklist::where('created_by', creatorId())
                ->where(function($q) use ($candidateonboarding) {
                    $q->where('status', 1)
                      ->orWhere('id', $candidateonboarding->checklist_id);
                })
                ->select('id', 'name')
                ->get();

            return response()->json([
                'candidates' => $candidates,
                'onboardingchecklists' => $checklists,
                'users' => User::emp()->where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return response()->json([], 403);
        }
    }

    public function update(UpdateCandidateOnboardingRequest $request, CandidateOnboarding $candidateonboarding)
    {
        if (Auth::user()->can('edit-candidate-onboardings')) {
            $validated = $request->validated();

            $candidateonboarding->candidate_id = $validated['candidate_id'];
            $candidateonboarding->checklist_id = $validated['checklist_id'];
            $candidateonboarding->start_date = $validated['start_date'];
            $candidateonboarding->buddy_employee_id = $validated['buddy_employee_id'] ?: null;
            $candidateonboarding->status = $validated['status'] ?? $candidateonboarding->status;

            $candidateonboarding->save();

            UpdateCandidateOnboarding::dispatch($request, $candidateonboarding);

            return redirect()->back()->with('success', __('The candidate onboarding details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.candidate-onboardings.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(CandidateOnboarding $candidateonboarding)
    {
        if (Auth::user()->can('delete-candidate-onboardings')) {
            DestroyCandidateOnboarding::dispatch($candidateonboarding);
            $candidateonboarding->delete();

            return redirect()->back()->with('success', __('The candidate onboarding has been deleted.'));
        } else {
            return redirect()->route('recruitment.candidate-onboardings.index')->with('error', __('Permission denied'));
        }
    }

    public function getChecklistsByCandidate($candidateId)
    {
        if (Auth::user()->can('manage-onboarding-checklists') || Auth::user()->can('create-candidate-onboardings') || Auth::user()->can('edit-candidate-onboardings')) {
            $checklists = OnboardingChecklist::where('created_by', creatorId())
                ->select('id', 'name')
                ->where('status', 1)
                ->get();

            return response()->json($checklists);
        } else {
            return response()->json([], 403);
        }
    }
}
