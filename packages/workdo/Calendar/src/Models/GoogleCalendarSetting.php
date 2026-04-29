<?php

namespace Workdo\Calendar\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleCalendarSetting extends Model
{
    protected $fillable = [
        'service_account_json',
        'calendar_id',
        'created_by'
    ];

    public static function getGoogleCalendarService()
    {
        try {
            $setting = self::where('created_by', creatorId())->first();
            
            if (!$setting || !$setting->service_account_json) {
                return null;
            }

            $jsonData = json_decode($setting->service_account_json, true);
            if (!$jsonData) {
                return null;
            }

            // Create temporary file for Google API
            $tempPath = storage_path('app/temp_google_calendar_' . creatorId() . '.json');
            file_put_contents($tempPath, $setting->service_account_json);

            $client = new \Google_Client();
            $client->setAuthConfig($tempPath);
            $client->addScope(\Google_Service_Calendar::CALENDAR);

            return new \Google_Service_Calendar($client);
        } catch (\Exception $e) {
            \Log::error('Google Calendar service error: ' . $e->getMessage());
            return null;
        }
    }
}