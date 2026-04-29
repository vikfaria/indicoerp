<?php

namespace Workdo\Xendit\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Xendit\Http\Requests\UpdateXenditSettingsRequest;

class XenditSettingsController extends Controller
{
    public function update(UpdateXenditSettingsRequest $request)
    {
        if (Auth::user()->can('edit-xendit-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "xendit_enabled");
                }

                return redirect()->back()->with('success', __('Xendit settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update xendit settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}