<?php

namespace Workdo\Mollie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Mollie\Http\Requests\UpdateMollieSettingsRequest;

class MollieSettingsController extends Controller
{
    public function update(UpdateMollieSettingsRequest $request)
    {
        if (Auth::user()->can('edit-mollie-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "mollie_enabled");
                }

                return redirect()->back()->with('success', __('Mollie settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Mollie settings: ') . $e->getMessage());
            }
        }
        return back()->with('error', __('Permission denied'));
    }
}