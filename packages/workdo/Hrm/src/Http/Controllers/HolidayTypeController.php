<?php

namespace Workdo\Hrm\Http\Controllers;

use Workdo\Hrm\Models\HolidayType;
use Workdo\Hrm\Http\Requests\StoreHolidayTypeRequest;
use Workdo\Hrm\Http\Requests\UpdateHolidayTypeRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Workdo\Hrm\Events\CreateHolidayType;
use Workdo\Hrm\Events\DestroyHolidayType;
use Workdo\Hrm\Events\UpdateHolidayType;


class HolidayTypeController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-holiday-types')){
            $holidaytypes = HolidayType::select('id', 'holiday_type', 'created_at')
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-holiday-types')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-holiday-types')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->latest()
                ->get();

            return Inertia::render('Hrm/SystemSetup/HolidayTypes/Index', [
                'holidaytypes' => $holidaytypes,

            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreHolidayTypeRequest $request)
    {
        if(Auth::user()->can('create-holiday-types')){
            $validated = $request->validated();



            $holidaytype = new HolidayType();
            $holidaytype->holiday_type = $validated['holiday_type'];

            $holidaytype->creator_id = Auth::id();
            $holidaytype->created_by = creatorId();
            $holidaytype->save();

            CreateHolidayType::dispatch($request, $holidaytype);

            return redirect()->route('hrm.holiday-types.index')->with('success', __('The holidaytype has been created successfully.'));
        }
        else{
            return redirect()->route('hrm.holiday-types.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateHolidayTypeRequest $request, HolidayType $holidaytype)
    {
        if(Auth::user()->can('edit-holiday-types')){
            $validated = $request->validated();



            $holidaytype->holiday_type = $validated['holiday_type'];

            $holidaytype->save();

            UpdateHolidayType::dispatch($request, $holidaytype);

            return redirect()->route('hrm.holiday-types.index')->with('success', __('The holidaytype details are updated successfully.'));
        }
        else{
            return redirect()->route('hrm.holiday-types.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(HolidayType $holidaytype)
    {
        if(Auth::user()->can('delete-holiday-types')){
            DestroyHolidayType::dispatch($holidaytype);
            $holidaytype->delete();

            return redirect()->route('hrm.holiday-types.index')->with('success', __('The holidaytype has been deleted.'));
        }
        else{
            return redirect()->route('hrm.holiday-types.index')->with('error', __('Permission denied'));
        }
    }


}