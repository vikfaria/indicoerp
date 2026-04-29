<?php

namespace Workdo\Coingate\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Coingate\Http\Requests\UpdateCoingateSettingsRequest;
use Illuminate\Support\Facades\Auth;

class CoingateSettingsController extends Controller
{
    public function update(UpdateCoingateSettingsRequest $request)
    {
        if (Auth::user()->can('edit-coingate-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "coingate_enabled");
                }

                return redirect()->back()->with('success', __('CoinGate settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update CoinGate settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}