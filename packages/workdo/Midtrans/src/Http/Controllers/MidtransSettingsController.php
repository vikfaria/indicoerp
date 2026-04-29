<?php

namespace Workdo\Midtrans\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Workdo\Midtrans\Http\Requests\UpdateMidtransSettingsRequest;

class MidtransSettingsController extends Controller
{
    public function update(UpdateMidtransSettingsRequest $request)
    {
        if (Auth::user()->can('manage-midtrans-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "midtrans_enabled");
                }

                return redirect()->back()->with('success', __('Midtrans settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Midtrans settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}
