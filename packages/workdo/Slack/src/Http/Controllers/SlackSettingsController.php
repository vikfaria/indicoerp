<?php

namespace Workdo\Slack\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SlackSettingsController extends Controller
{
    public function index()
    {
        $slackNotifications = Notification::where('type', 'Slack')->get()->groupBy('module');

        return response()->json([
            'slackNotifications' => $slackNotifications
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('edit-slack-settings')) {

            $validator = Validator::make($request->all(), [
                'settings.slack_webhook_url' => 'required|string|max:255',
                'settings.slack_notification_is' => 'required|string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->with('error', __('Validation failed'));
            }

            $settings = $request->input('settings', []);
            try {
                foreach ($settings as $key => $value) {
                    setSetting($key, $value, creatorId());
                }

                return redirect()->back()->with('success', __('Slack settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update slack settings: ') . $e->getMessage());
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
