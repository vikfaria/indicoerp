<?php

namespace Workdo\CinetPay\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\CinetPay\Http\Requests\UpdateCinetPaySettingsRequest;
use Illuminate\Support\Facades\Auth;

class CinetPaySettingsController extends Controller
{
    public function update(UpdateCinetPaySettingsRequest $request)
    {
        if (Auth::user()->can('edit-cinetpay-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "cinetpay_enabled");
                }

                return redirect()->back()->with('success', __('CinetPay settings saved successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update CinetPay settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}