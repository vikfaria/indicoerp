<?php

namespace Workdo\Recruitment\Http\Controllers;

use Workdo\Recruitment\Models\JobType;
use Workdo\Recruitment\Http\Requests\StoreJobTypeRequest;
use Workdo\Recruitment\Http\Requests\UpdateJobTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Recruitment\Events\CreateJobType;
use Workdo\Recruitment\Events\UpdateJobType;
use Workdo\Recruitment\Events\DestroyJobType;


class JobTypeController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-job-types')) {
            $jobtypes = JobType::select('id', 'name', 'description', 'is_active', 'created_at')
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-job-types')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-job-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Recruitment/SystemSetup/JobTypes/Index', [
                'jobtypes' => $jobtypes,

            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreJobTypeRequest $request)
    {
        if (Auth::user()->can('create-job-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $jobtype = new JobType();
            $jobtype->name = $validated['name'];
            $jobtype->description = $validated['description'];
            $jobtype->is_active = $validated['is_active'];
            $jobtype->is_active = $validated['is_active'];
            $jobtype->creator_id = Auth::id();
            $jobtype->created_by = creatorId();
            $jobtype->save();

            CreateJobType::dispatch($request, $jobtype);

            return redirect()->route('recruitment.job-types.index')->with('success', __('The job type has been created successfully.'));
        } else {
            return redirect()->route('recruitment.job-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateJobTypeRequest $request, JobType $jobtype)
    {
        if (Auth::user()->can('edit-job-types')) {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_active'] = $request->boolean('is_active', false);

            $jobtype->name = $validated['name'];
            $jobtype->description = $validated['description'];
            $jobtype->is_active = $validated['is_active'];
            $jobtype->is_active = $validated['is_active'];
            $jobtype->save();

            UpdateJobType::dispatch($request, $jobtype);

            return redirect()->back()->with('success', __('The job type details are updated successfully.'));
        } else {
            return redirect()->route('recruitment.job-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(JobType $jobtype)
    {
        if (Auth::user()->can('delete-job-types')) {
            DestroyJobType::dispatch($jobtype);

            $jobtype->delete();

            return redirect()->back()->with('success', __('The job type has been deleted.'));
        } else {
            return redirect()->route('recruitment.job-types.index')->with('error', __('Permission denied'));
        }
    }
}
