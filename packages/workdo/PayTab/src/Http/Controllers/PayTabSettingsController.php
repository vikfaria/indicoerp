<?php

namespace Workdo\PayTab\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\PayTab\Http\Requests\UpdatePayTabSettingsRequest;
use Illuminate\Support\Facades\Auth;

class PayTabSettingsController extends Controller
{
    public function update(UpdatePayTabSettingsRequest $request)
    {
        if (Auth::user()->can('edit-paytab-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "paytab_payment_is_on");
                }

                return redirect()->back()->with('success', __('PayTab settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update PayTab settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}