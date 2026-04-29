<?php

namespace Workdo\ZoomMeeting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ZoomMeetingSettingsController extends Controller
{
    public function update(Request $request)
    {
        if (Auth::user()->can('edit-zoom-meeting-settings')) {

            $rules = [
                'settings.zoom_enabled' => 'nullable|string|in:on,off',
                'settings.zoom_webhook_secret' => 'nullable|string|max:255',
            ];

            if ($request->input('settings.zoom_enabled') === 'on') {
                $rules['settings.zoom_api_key'] = 'required|string|max:255';
                $rules['settings.zoom_api_secret'] = 'required|string|max:255';
                $rules['settings.zoom_account_id'] = 'required|string|max:255';
            } else {
                $rules['settings.zoom_api_key'] = 'nullable|string|max:255';
                $rules['settings.zoom_api_secret'] = 'nullable|string|max:255';
                $rules['settings.zoom_account_id'] = 'nullable|string|max:255';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', __('Validation failed'));
            }

            $allowedSettings = [
                'zoom_api_key',
                'zoom_api_secret',
                'zoom_account_id',
                'zoom_enabled',
                'zoom_webhook_secret'
            ];

            $settings = $request->input('settings', []);
            try {
                foreach ($settings as $key => $value) {
                    if (in_array($key, $allowedSettings)) {
                        setSetting($key, $value, creatorId(),false);
                    }
                }

                return redirect()->back()->with('success', __('Zoom Meeting settings saved successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update Zoom Meeting settings: ') . $e->getMessage());
            }

        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
