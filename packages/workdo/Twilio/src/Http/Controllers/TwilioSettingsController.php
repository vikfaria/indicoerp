<?php

namespace Workdo\Twilio\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TwilioSettingsController extends Controller
{
    public function index()
    {
        $twilioNotifications = Notification::where('type', 'Twilio')->get()->groupBy('module');

        return response()->json([
            'twilioNotifications' => $twilioNotifications
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('edit-twilio-settings')) {

            $validator = Validator::make($request->all(), [
                'settings.twilio_sid'             => 'required|string|max:255',
                'settings.twilio_token'           => 'required|string|max:255',
                'settings.twilio_from'            => 'required|string|max:255',
                'settings.twilio_notification_is' => 'required|string',
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

                return redirect()->back()->with('success', __('Twilio settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update twilio settings: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}
