<?php

namespace Workdo\Toyyibpay\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Toyyibpay\Http\Requests\UpdateToyyibpaySettingsRequest;
use Illuminate\Support\Facades\Auth;

class ToyyibpaySettingsController extends Controller
{
    public function update(UpdateToyyibpaySettingsRequest $request)
    {
        if (Auth::user()->can('edit-toyyibpay-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "toyyibpay_enabled");
                }

                return redirect()->back()->with('success', __('Toyyibpay settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Toyyibpay settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}