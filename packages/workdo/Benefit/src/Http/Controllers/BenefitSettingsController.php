<?php

namespace Workdo\Benefit\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Benefit\Http\Requests\UpdateBenefitSettingsRequest;
use Illuminate\Support\Facades\Auth;

class BenefitSettingsController extends Controller
{
    public function update(UpdateBenefitSettingsRequest $request)
    {
        if (Auth::user()->can('edit-benefit-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "benefit_enabled");
                }

                return redirect()->back()->with('success', __('Benefit settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Benefit settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}