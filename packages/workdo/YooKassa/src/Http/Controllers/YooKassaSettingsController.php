<?php

namespace Workdo\YooKassa\Http\Controllers;

use App\Http\Controllers\Controller;
use Workdo\YooKassa\Http\Requests\UpdateYooKassaSettingsRequest;
use Illuminate\Support\Facades\Auth;

class YooKassaSettingsController extends Controller
{
    public function update(UpdateYooKassaSettingsRequest $request)
    {
        if (Auth::user()->can('edit-yookassa-settings')) {
            $validated = $request->validated();

            $settings = $validated['settings'];
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId(), $key == "yookassa_enabled");
                }

                return redirect()->back()->with('success', __('YooKassa settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update YooKassa settings: ') . $e->getMessage());
            }           
        }
        return back()->with('error', __('Permission denied'));
    }
}