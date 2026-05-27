<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\LeaveType;
use Workdo\Hrm\Http\Requests\StoreLeaveTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateLeaveTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateLeaveType;
use Workdo\Hrm\Events\DestroyLeaveType;
use Workdo\Hrm\Events\UpdateLeaveType;


class LeaveTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-leave-types')){
            $leavetypes = LeaveType::query()
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-leave-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-leave-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), function($q) {
                    $q->where(function($query) {
                    $query->where('name', 'like', '%' . request('name') . '%');
                    });
                })
                
                ->when(request('is_paid') !== null, function($q) {
                    $q->where('is_paid', request('is_paid'));
                })

                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/LeaveTypes/Index', [
                'leavetypes' => $leavetypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreLeaveTypeRequest $request)
    {
        if(Auth::user()->can('create-leave-types')){
            $validated = $this->applyMozambiqueLegalDefaults($request->validated());

            $validated['is_paid'] = $request->boolean('is_paid', false);

            $leavetype = new LeaveType();
            $leavetype->name = $validated['name'];
            $leavetype->legal_code = $validated['legal_code'] ?? null;
            $leavetype->description = $validated['description'];
            $leavetype->max_days_per_year = $validated['max_days_per_year'];
            $leavetype->is_paid = $validated['is_paid'];
            $leavetype->requires_supporting_document = $validated['requires_supporting_document'] ?? false;
            $leavetype->must_be_consecutive = $validated['must_be_consecutive'] ?? false;
            $leavetype->fixed_duration_days = $validated['fixed_duration_days'] ?? null;
            $leavetype->min_advance_notice_days = $validated['min_advance_notice_days'] ?? null;
            $leavetype->pre_event_start_window_days = $validated['pre_event_start_window_days'] ?? null;
            $leavetype->post_event_start_offset_days = $validated['post_event_start_offset_days'] ?? null;
            $leavetype->allow_cash_out = $validated['allow_cash_out'] ?? false;
            $leavetype->min_effective_rest_days = $validated['min_effective_rest_days'] ?? null;
            $leavetype->color = $validated['color'];

            $leavetype->creator_id = Auth::id();
            $leavetype->created_by = creatorId();
            $leavetype->save();

            CreateLeaveType::dispatch($request, $leavetype);

            return redirect()->route('hrm.leave-types.index')->with('success', __('The leavetype has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.leave-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leavetype)
    {
        if(Auth::user()->can('edit-leave-types')){
            $validated = $this->applyMozambiqueLegalDefaults($request->validated());

            $validated['is_paid'] = $request->boolean('is_paid', false);

            $leavetype->name = $validated['name'];
            $leavetype->legal_code = $validated['legal_code'] ?? null;
            $leavetype->description = $validated['description'];
            $leavetype->max_days_per_year = $validated['max_days_per_year'];
            $leavetype->is_paid = $validated['is_paid'];
            $leavetype->requires_supporting_document = $validated['requires_supporting_document'] ?? false;
            $leavetype->must_be_consecutive = $validated['must_be_consecutive'] ?? false;
            $leavetype->fixed_duration_days = $validated['fixed_duration_days'] ?? null;
            $leavetype->min_advance_notice_days = $validated['min_advance_notice_days'] ?? null;
            $leavetype->pre_event_start_window_days = $validated['pre_event_start_window_days'] ?? null;
            $leavetype->post_event_start_offset_days = $validated['post_event_start_offset_days'] ?? null;
            $leavetype->allow_cash_out = $validated['allow_cash_out'] ?? false;
            $leavetype->min_effective_rest_days = $validated['min_effective_rest_days'] ?? null;
            $leavetype->color = $validated['color'];

            $leavetype->save();

            UpdateLeaveType::dispatch($request, $leavetype);

            return redirect()->back()->with('success', __('The leavetype details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.leave-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(LeaveType $leavetype)
    {
        if(Auth::user()->can('delete-leave-types')){
            DestroyLeaveType::dispatch($leavetype);
            $leavetype->delete();

            return redirect()->back()->with('success', __('The leavetype has been deleted.'));
        }
        else{
            return redirect()->route('hrm.leave-types.index')->with('error', __('Permission denied'));
        }
    }




    private function applyMozambiqueLegalDefaults(array $validated): array
    {
        $legalCode = $validated['legal_code'] ?? null;

        if ($legalCode === 'maternity') {
            $validated['fixed_duration_days'] = $validated['fixed_duration_days'] ?? 90;
            $validated['must_be_consecutive'] = true;
            $validated['pre_event_start_window_days'] = $validated['pre_event_start_window_days'] ?? 20;
        }

        if ($legalCode === 'paternity') {
            $validated['fixed_duration_days'] = $validated['fixed_duration_days'] ?? 7;
            $validated['must_be_consecutive'] = true;
            $validated['post_event_start_offset_days'] = $validated['post_event_start_offset_days'] ?? 1;
        }

        if ($legalCode === 'sick_leave') {
            $validated['requires_supporting_document'] = true;
        }

        return $validated;
    }
}
