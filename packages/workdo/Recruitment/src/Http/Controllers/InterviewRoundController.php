<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\InterviewRound;
use Workdo\Recruitment\Http\Requests\StoreInterviewRoundRequest;
use Workdo\Recruitment\Http\Requests\UpdateInterviewRoundRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Models\JobPosting;
use Workdo\Recruitment\Events\CreateInterviewRound;
use Workdo\Recruitment\Events\UpdateInterviewRound;
use Workdo\Recruitment\Events\DestroyInterviewRound;

class InterviewRoundController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-interview-rounds')) {
            $query = InterviewRound::query()
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-interview-rounds')) {
                        $q->where('interview_rounds.created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-interview-rounds')) {
                        $q->where('interview_rounds.creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('interview_rounds.name', 'like', '%' . request('name') . '%');
                        $query->orWhere('interview_rounds.description', 'like', '%' . request('name') . '%');
                    });
                })
                ->when(request('job_id') && request('job_id') !== 'all', fn($q) => $q->where('interview_rounds.job_id', request('job_id')))
                ->when(request('status') !== null && request('status') !== '', fn($q) => $q->where('interview_rounds.status', request('status')));

            // Handle sorting
            if (request('sort') === 'job_posting.title') {
                $direction = request('direction', 'asc');
                $query->join('job_postings', 'interview_rounds.job_id', '=', 'job_postings.id')
                    ->orderBy('job_postings.title', $direction)
                    ->select('interview_rounds.*');
            } elseif (request('sort') === 'sequence_number') {
                $query->orderBy('interview_rounds.sequence_number', request('direction', 'asc'));
            } elseif (request('sort')) {
                $query->orderBy('interview_rounds.' . request('sort'), request('direction', 'asc'));
            } else {
                $query->latest('interview_rounds.created_at');
            }

            $interviewrounds = $query->paginate(request('per_page', 10))->withQueryString();

            // Load relationships after pagination
            $interviewrounds->load('job_posting');

            return Inertia::render('Recruitment/InterviewRounds/Index', [
                'interviewrounds' => $interviewrounds,
                'jobpostings' => JobPosting::where('created_by', creatorId())->where('is_published', 1)->where('status', 'active')->select('id', 'title')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreInterviewRoundRequest $request)
    {
        if (Auth::user()->can('create-interview-rounds')) {
            $validated = $request->validated();

            $interviewround = new InterviewRound();
            $interviewround->name = $validated['name'];
            $interviewround->sequence_number = $validated['sequence_number'];
            $interviewround->description = $validated['description'];
            $interviewround->status = $validated['status'];
            $interviewround->job_id = $validated['job_id'];

            $interviewround->creator_id = Auth::id();
            $interviewround->created_by = creatorId();
            $interviewround->save();

            CreateInterviewRound::dispatch($request, $interviewround);

            return redirect()->route('recruitment.interview-rounds.index')->with('success', __('The interview round has been created successfully.'));
        } else {
            return redirect()->route('recruitment.interview-rounds.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateInterviewRoundRequest $request, InterviewRound $interviewround)
    {
        if (Auth::user()->can('edit-interview-rounds')) {
            $validated = $request->validated();

            $interviewround->name = $validated['name'];
            $interviewround->sequence_number = $validated['sequence_number'];
            $interviewround->description = $validated['description'];
            $interviewround->status = $validated['status'];
            $interviewround->job_id = $validated['job_id'];

            $interviewround->save();

            UpdateInterviewRound::dispatch($request, $interviewround);

            return redirect()->back()->with('success', __('The interview round details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.interview-rounds.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(InterviewRound $interviewround)
    {
        if (Auth::user()->can('delete-interview-rounds')) {
            DestroyInterviewRound::dispatch($interviewround);
            $interviewround->delete();

            return redirect()->back()->with('success', __('The interview round has been deleted.'));
        } else {
            return redirect()->route('recruitment.interview-rounds.index')->with('error', __('Permission denied'));
        }
    }
}
