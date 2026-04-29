<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\InterviewType;
use Workdo\Recruitment\Http\Requests\StoreInterviewTypeRequest;
use Workdo\Recruitment\Http\Requests\UpdateInterviewTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Events\CreateInterviewType;
use Workdo\Recruitment\Events\UpdateInterviewType;
use Workdo\Recruitment\Events\DestroyInterviewType;


class InterviewTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-interview-types')) {
            $interviewtypes = InterviewType::select('id', 'name', 'description', 'is_active', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-interview-types')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-interview-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Recruitment/SystemSetup/InterviewTypes/Index', [
                'interviewtypes' => $interviewtypes,

            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreInterviewTypeRequest $request)
    {
        if (Auth::user()->can('create-interview-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $interviewtype = new InterviewType();
            $interviewtype->name = $validated['name'];
            $interviewtype->description = $validated['description'];
            $interviewtype->is_active = $validated['is_active'];
            $interviewtype->is_active = $validated['is_active'];
            $interviewtype->creator_id = Auth::id();
            $interviewtype->created_by = creatorId();
            $interviewtype->save();

            CreateInterviewType::dispatch($request, $interviewtype);

            return redirect()->route('recruitment.interview-types.index')->with('success', __('The interview type has been created successfully.'));
        } else {
            return redirect()->route('recruitment.interview-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateInterviewTypeRequest $request, InterviewType $interviewtype)
    {
        if (Auth::user()->can('edit-interview-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $interviewtype->name = $validated['name'];
            $interviewtype->description = $validated['description'];
            $interviewtype->is_active = $validated['is_active'];
            $interviewtype->is_active = $validated['is_active'];
            $interviewtype->save();

            UpdateInterviewType::dispatch($request, $interviewtype);

            return redirect()->back()->with('success', __('The interview type details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.interview-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(InterviewType $interviewtype)
    {
        if (Auth::user()->can('delete-interview-types')) {
            DestroyInterviewType::dispatch($interviewtype);
            $interviewtype->delete();

            return redirect()->back()->with('success', __('The interview type has been deleted.'));
        } else {
            return redirect()->route('recruitment.interview-types.index')->with('error', __('Permission denied'));
        }
    }
}
