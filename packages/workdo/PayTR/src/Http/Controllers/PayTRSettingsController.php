<?php

namespace Workdo\PayTR\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\PayTR\Http\Requests\UpdatePayTRSettingsRequest;
use Illuminate\Support\Facades\Auth;

class PayTRSettingsController extends Controller
{
    public function update(UpdatePayTRSettingsRequest $request)
    {
        if (Auth::user()->can('edit-paytr-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(),$key == 'paytr_enabled');
                }

                return redirect()->back()->with('success', __('PayTR settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update PayTR settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}