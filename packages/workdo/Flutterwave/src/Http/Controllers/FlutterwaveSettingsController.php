<?php

namespace Workdo\Flutterwave\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Flutterwave\Http\Requests\UpdateFlutterwaveSettingsRequest;
use Illuminate\Support\Facades\Auth;

class FlutterwaveSettingsController extends Controller
{
    public function update(UpdateFlutterwaveSettingsRequest $request)
    {
        if (Auth::user()->can('edit-flutterwave-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "flutterwave_enabled");
                }

                return redirect()->back()->with('success', __('Flutterwave settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Flutterwave settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}