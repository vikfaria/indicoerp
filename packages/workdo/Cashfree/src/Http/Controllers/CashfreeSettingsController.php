<?php

namespace Workdo\Cashfree\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\Cashfree\Http\Requests\UpdateCashfreeSettingsRequest;
use Illuminate\Support\Facades\Auth;

class CashfreeSettingsController extends Controller
{
    public function update(UpdateCashfreeSettingsRequest $request)
    {
        if (Auth::user()->can('edit-cashfree-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "cashfree_enabled");
                }

                return redirect()->back()->with('success', __('Cashfree settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Cashfree settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}