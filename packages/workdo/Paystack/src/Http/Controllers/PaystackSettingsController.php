<?php

namespace Workdo\Paystack\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Paystack\Http\Requests\UpdatePaystackSettingsRequest;
use Illuminate\Support\Facades\Auth;

class PaystackSettingsController extends Controller
{
    public function update(UpdatePaystackSettingsRequest $request)
    {
        if (Auth::user()->can('edit-paystack-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "paystack_enabled");
                }

                return redirect()->back()->with('success', __('Paystack settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Paystack settings: ') . $e->getMessage());
            }
        }
        return back()->with('error', __('Permission denied.'));
    }
}