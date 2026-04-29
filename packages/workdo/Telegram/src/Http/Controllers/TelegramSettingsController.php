<?php

namespace Workdo\Telegram\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TelegramSettingsController extends Controller
{
    public function index()
    {
        $telegramNotifications = Notification::where('type', 'Telegram')->get()->groupBy('module');

        return response()->json([
            'telegramNotifications' => $telegramNotifications
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('edit-telegram-settings')) {

            $validator = Validator::make($request->all(), [
                'settings.telegram_bot_token' => 'required|string|max:255',
                'settings.telegram_chat_id' => 'required|string|max:255',
                'settings.telegram_notification_is' => 'required|string',
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

                return redirect()->back()->with('success', __('Telegram settings save successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to update telegram settings: ') . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }
}