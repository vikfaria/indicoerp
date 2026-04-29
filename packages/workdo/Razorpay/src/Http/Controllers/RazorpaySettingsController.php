<?php

namespace Workdo\Razorpay\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Razorpay\Http\Requests\UpdateRazorpaySettingsRequest;
use Illuminate\Support\Facades\Auth;

class RazorpaySettingsController extends Controller
{
    public function update(UpdateRazorpaySettingsRequest $request)
    {
        if (Auth::user()->can('edit-razorpay-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "razorpay_enabled");
                }

                return redirect()->back()->with('success', __('Razorpay settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Razorpay settings: ') . $e->getMessage());
            }
        }
        return back()->with('error', __('Permission denied'));
    }
}