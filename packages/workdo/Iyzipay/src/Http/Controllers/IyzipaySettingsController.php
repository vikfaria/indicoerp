<?php

namespace Workdo\Iyzipay\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Iyzipay\Http\Requests\UpdateIyzipaySettingsRequest;
use Illuminate\Support\Facades\Auth;

class IyzipaySettingsController extends Controller
{
    public function update(UpdateIyzipaySettingsRequest $request)
    {
        if (Auth::user()->can('edit-iyzipay-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "iyzipay_enabled");
                }

                return redirect()->back()->with('success', __('Iyzipay settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Iyzipay settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}