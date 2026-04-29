<?php

namespace Workdo\Payfast\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Payfast\Http\Requests\UpdatePayfastSettingsRequest;
use Illuminate\Support\Facades\Auth;

class PayfastSettingsController extends Controller
{
    public function update(UpdatePayfastSettingsRequest $request)
    {
        if (Auth::user()->can('edit-payfast-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "payfast_enabled");
                }

                return redirect()->back()->with('success', __('Payfast settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Payfast settings: ') . $e->getMessage());
            }
        }
        return back()->with('error', __('Permission denied'));
    }
}