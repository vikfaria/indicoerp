<?php

namespace Workdo\Aamarpay\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Aamarpay\Http\Requests\UpdateAamarpaySettingsRequest;

class AamarpaySettingsController extends Controller
{
    public function update(UpdateAamarpaySettingsRequest $request)
    {
        if (Auth::user()->can('edit-aamarpay-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "aamarpay_enabled");
                }

                return redirect()->back()->with('success', __('Aamarpay settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Aamarpay settings: ') . $e->getMessage());
            }
        }
        return back()->with('error', __('Permission denied'));
    }
}
