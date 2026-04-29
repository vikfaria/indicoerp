<?php

namespace Workdo\Tap\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Tap\Http\Requests\UpdateTapSettingsRequest;
use Illuminate\Support\Facades\Auth;

class TapSettingsController extends Controller
{
    public function update(UpdateTapSettingsRequest $request)
    {
        if (Auth::user()->can('edit-tap-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "tap_enabled");
                }

                return redirect()->back()->with('success', __('Tap settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Tap settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}