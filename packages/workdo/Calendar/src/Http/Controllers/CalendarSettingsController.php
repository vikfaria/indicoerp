<?php

namespace Workdo\Calendar\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarSettingsController extends Controller
{
    public function index()
    {
        $tokenExists = company_setting('google_calendar_token', creatorId());
        $refreshTokenExists = company_setting('google_calendar_refresh_token', creatorId());
        $hasTokens = $tokenExists && $refreshTokenExists;

        return response()->json([
            'hasTokens' => $hasTokens
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('edit-google-calendar-settings')) {
            $request->validate([
                'settings.google_calendar_json_file' => 'nullable|string',
                'settings.google_calendar_id' => 'nullable|string',
                'settings.google_calendar_enable' => 'required|string',
                'json_file' => 'nullable|file|mimes:json|max:2048',
            ]);

            $settings = $request->input('settings', []);

            if ($request->hasFile('json_file')) {
                $jsonContent = file_get_contents($request->file('json_file')->getRealPath());
                $settings['google_calendar_json_file'] = $jsonContent;
            }

            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId());
                }

                return back()->with('success', __('Google Calendar settings saved successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', __('Failed to update settings: ') . $e->getMessage());
            }
        } else {
            return back()->with('error', __('Permission denied.'));
        }
    }
}
