<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\Shift;
use Workdo\Hrm\Http\Requests\StoreShiftRequest;
use Workdo\Hrm\Http\Requests\UpdateShiftRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\User;
use Workdo\Hrm\Events\CreateShift;
use Workdo\Hrm\Events\UpdateShift;
use Workdo\Hrm\Events\DestroyShift;

class ShiftController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-shifts')) {
            $shifts = Shift::query()
                ->with(['creator'])
                ->where(function ($q) {
                    if (Auth::user()->can('manage-any-shifts')) {
                        $q->where('created_by', creatorId());
                    } elseif (Auth::user()->can('manage-own-shifts')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('shift_name'), function ($q) {
                    $q->where(function ($query) {
                        $query->where('shift_name', 'like', '%' . request('shift_name') . '%');
                        $query->orWhere('created_by', 'like', '%' . request('shift_name') . '%');
                    });
                })
                ->when(request('creator_id') && request('creator_id') !== '', fn($q) => $q->where('creator_id', request('creator_id')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('Hrm/Shifts/Index', [
                'shifts' => $shifts,
                'users' => User::where('created_by', creatorId())->select('id', 'name')->get(),
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreShiftRequest $request)
    {
        if (Auth::user()->can('create-shifts')) {
            $validated = $request->validated();
            $shift = new Shift();
            $shift->shift_name = $validated['shift_name'];
            $shift->start_time = $validated['start_time'];
            $shift->end_time = $validated['end_time'];
            $shift->break_start_time = $validated['break_start_time'];
            $shift->break_end_time = $validated['break_end_time'];
            $shift->is_night_shift = $validated['is_night_shift'];
            $shift->creator_id = Auth::id();
            $shift->created_by = creatorId();
            $shift->save();

            CreateShift::dispatch($request, $shift);

            return redirect()->route('hrm.shifts.index')->with('success', __('The shift has been created successfully.'));
        } else {
            return redirect()->route('hrm.shifts.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateShiftRequest $request, Shift $shift)
    {
        if (Auth::user()->can('edit-shifts')) {
            $validated = $request->validated();
            $shift->shift_name = $validated['shift_name'];
            $shift->start_time = $validated['start_time'];
            $shift->end_time = $validated['end_time'];
            $shift->break_start_time = $validated['break_start_time'];
            $shift->break_end_time = $validated['break_end_time'];
            $shift->is_night_shift = $validated['is_night_shift'];
            $shift->save();

            UpdateShift::dispatch($request, $shift);

            return redirect()->back()->with('success', __('The shift details are updated successfully.'));
        } else {
            return redirect()->route('hrm.shifts.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Shift $shift)
    {
        if (Auth::user()->can('delete-shifts')) {
            DestroyShift::dispatch($shift);
            $shift->delete();

            return redirect()->back()->with('success', __('The shift has been deleted.'));
        } else {
            return redirect()->route('hrm.shifts.index')->with('error', __('Permission denied'));
        }
    }
}
