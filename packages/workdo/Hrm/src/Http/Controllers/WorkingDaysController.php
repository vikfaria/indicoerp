<?php

namespace Workdo\Hrm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkingDaysController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-working-days')) {
            $globalSettings = getCompanyAllSetting();
            $workingDaysIndices = $globalSettings['working_days'] ?? '';
            $workingDaysArray = json_decode($workingDaysIndices, true) ?? [];

            $workingDayNames = [];
            if (!empty($workingDaysArray)) {
                // Convert indices back to day names using date function
                $workingDayNames = array_map(function ($index) {
                    return strtolower(date('l', strtotime("Sunday +{$index} days")));
                }, $workingDaysArray);
            }

            return Inertia::render('Hrm/SystemSetup/WorkingDays/Index', [
                'workingDays' => $workingDayNames,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(Request $request)
    {
        if (Auth::user()->can('edit-working-days')) {
            $request->validate([
                'working_days' => 'required|array|min:1',
                'working_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'
            ], [
                'working_days.required' => __('At least one working day must be selected.'),
                'working_days.array' => __('Working days must be an array.'),
                'working_days.min' => __('At least one working day must be selected.'),
                'working_days.*.in' => __('Invalid day selected.'),
            ]);

            // Convert day names to indices using date function
            $workingDayIndices = array_map(function ($day) {
                return date('w', strtotime($day));
            }, $request->working_days);

            setSetting('working_days', json_encode($workingDayIndices));

            return redirect()->back()->with('success', __('Working days updated successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
